<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\ImpersonationToken;
use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\PlatformAdmin;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantPayment;
use App\Services\DatabaseBackup;
use App\Services\Tenancy\Billing;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Platform staff: every business, pausing and resuming them, their plan, and signing in for support.
 */
class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        return view('admin.tenants', [
            'tenants' => Tenant::with(['plan', 'domains'])
                ->when($search !== '', fn ($query) => $query->where(fn ($where) => $where
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('owner_email', 'like', "%{$search}%")))
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString(),
            'plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get(),
            'activity' => PlatformActivity::with(['admin:id,name', 'tenant:id,name'])->latest('id')->limit(15)->get(),
            'search' => $search,
        ]);
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        // Paused by hand: a payment won't resume it, only staff can
        $tenant->update(['status' => 'suspended', 'suspended_at' => now(), 'suspended_reason' => 'manual']);
        PlatformActivity::record('suspended', "Paused {$tenant->name}", $tenant, $this->admin($request));

        return back()->with('warning', "{$tenant->name} is paused: its users can't sign in.");
    }

    /**
     * Re-open a paused or closed business. If its paid time has run out, the next daily check pauses it again.
     */
    public function resume(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(in_array($tenant->status, ['suspended', 'cancelled'], true), 409);

        $tenant->update(['status' => 'active', 'suspended_at' => null, 'suspended_reason' => null, 'data_purge_after' => null]);
        PlatformActivity::record('resumed', "Resumed {$tenant->name}", $tenant, $this->admin($request));

        return back()->with('success', "{$tenant->name} is active again.");
    }

    /**
     * One business: its details, plan, paid time, payments and recent activity.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['plan', 'domains', 'payments' => fn ($query) => $query->with(['plan:id,name', 'admin:id,name'])->latest('paid_on')->latest('id')]);

        return view('admin.tenant', [
            'tenant' => $tenant,
            'plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get(),
            'methods' => TenantPayment::METHODS,
            'activity' => PlatformActivity::with('admin:id,name')->where('tenant_id', $tenant->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function recordPayment(Request $request, Tenant $tenant, Billing $billing): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'method' => ['required', Rule::in(array_keys(TenantPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'plan_id' => ['nullable', 'integer', Rule::exists('landlord.plans', 'id')],
        ]);

        $payment = $billing->recordPayment($tenant, $this->admin($request), $data);

        return back()->with('success', "Payment recorded. {$tenant->name} is paid until ".$payment->covers_until->format('j M Y').'.');
    }

    public function close(Request $request, Tenant $tenant, Billing $billing): RedirectResponse
    {
        $billing->close($tenant, $this->admin($request));

        return back()->with('warning', "{$tenant->name} is closed. Its data is kept until ".$tenant->refresh()->data_purge_after->format('j M Y').'.');
    }

    /**
     * A fresh backup of the business's database, e.g. to hand a closed business its data.
     */
    public function backup(Request $request, Tenant $tenant, TenantManager $tenancy, DatabaseBackup $backups): BinaryFileResponse
    {
        $path = $tenancy->run($tenant, fn () => $backups->path($backups->create()));
        PlatformActivity::record('backup_downloaded', "Downloaded a backup of {$tenant->name}", $tenant, $this->admin($request));

        return response()->download($path, "{$tenant->slug}-".basename($path));
    }

    public function plan(Request $request, Tenant $tenant): RedirectResponse
    {
        $planId = $request->validate([
            'plan_id' => ['nullable', 'integer', Rule::exists('landlord.plans', 'id')],
        ])['plan_id'] ?? null;

        $tenant->update(['plan_id' => $planId]);
        $planName = $tenant->plan?->name ?? 'no plan';
        PlatformActivity::record('plan_changed', "{$tenant->name} moved to {$planName}", $tenant, $this->admin($request));

        return back()->with('info', "{$tenant->name} is now on {$planName}.");
    }

    /**
     * Sign in to the business as its owner, through a one-time link that works for one minute.
     */
    public function impersonate(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($tenant->isUsable(), 409, 'Resume the business before signing in to it.');

        $token = ImpersonationToken::issue($this->admin($request), $tenant);

        return redirect()->away($tenant->url('support/'.$token));
    }

    private function admin(Request $request): PlatformAdmin
    {
        return $request->user('platform');
    }
}
