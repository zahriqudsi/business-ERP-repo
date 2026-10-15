<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform staff set up the plans businesses can be on, with their price and limits.
 * Plans are switched off rather than deleted, so past payments keep their plan.
 */
class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::withCount('tenants')->orderByDesc('is_active')->orderBy('price_monthly')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['currency' => 'LKR', 'is_public' => true, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = Plan::create($this->validated($request));
        PlatformActivity::record('plan_created', "Created plan {$plan->name}", admin: $request->user('platform'));

        return redirect()->route('admin.plans.index')->with('success', "Plan “{$plan->name}” added.");
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));
        PlatformActivity::record('plan_updated', "Changed plan {$plan->name}", admin: $request->user('platform'));

        return redirect()->route('admin.plans.index')->with('info', "Plan “{$plan->name}” updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('landlord.plans', 'code')->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:100'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'limits.users' => ['nullable', 'integer', 'min:1'],
            'limits.invoices_per_month' => ['nullable', 'integer', 'min:1'],
        ], [
            'code.unique' => 'Another plan already uses this code.',
        ]);

        // Empty limit boxes mean unlimited
        $data['limits'] = collect(Plan::LIMITS)->keys()
            ->mapWithKeys(fn (string $key) => [$key => isset($data['limits'][$key]) ? (int) $data['limits'][$key] : null])
            ->all();
        $data['currency'] = strtoupper($data['currency']);

        return $data + ['is_public' => $request->boolean('is_public'), 'is_active' => $request->boolean('is_active')];
    }
}
