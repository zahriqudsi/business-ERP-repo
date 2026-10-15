<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\ImpersonationToken;
use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\PlatformAdmin;
use App\Models\Landlord\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        $tenant->update(['status' => 'suspended', 'suspended_at' => now()]);
        PlatformActivity::record('suspended', "Paused {$tenant->name}", $tenant, $this->admin($request));

        return back()->with('warning', "{$tenant->name} is paused: its users can't sign in.");
    }

    public function resume(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active', 'suspended_at' => null]);
        PlatformActivity::record('resumed', "Resumed {$tenant->name}", $tenant, $this->admin($request));

        return back()->with('success', "{$tenant->name} is active again.");
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
