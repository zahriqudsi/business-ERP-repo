<?php

namespace App\Http\Controllers;

use App\Models\Landlord\TenantPayment;
use App\Services\Tenancy\PlanLimits;
use App\Services\Tenancy\TenantManager;
use Illuminate\View\View;

/**
 * The owner's Plan & billing page (tenancy only): plan, usage against its limits, paid time and payments.
 */
class BillingController extends Controller
{
    public function __invoke(TenantManager $tenancy, PlanLimits $limits): View
    {
        $tenant = $tenancy->current();
        abort_unless($tenant, 404);
        $tenant->load(['plan', 'payments' => fn ($query) => $query->latest('paid_on')->latest('id')->limit(24)]);

        return view('billing.show', [
            'tenant' => $tenant,
            'usage' => $limits->summary(),
            'methods' => TenantPayment::METHODS,
            'instructions' => (string) config('tenancy.billing.payment_instructions'),
        ]);
    }
}
