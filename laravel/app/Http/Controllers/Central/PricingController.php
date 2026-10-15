<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use Illuminate\View\View;

/**
 * Public pricing: the plans set up in the admin area that can be chosen and are shown publicly.
 */
class PricingController extends Controller
{
    public function __invoke(): View
    {
        $plans = Plan::where('is_active', true)->where('is_public', true)->orderBy('price_monthly')->get();

        return view('central.pricing', [
            'plans' => $plans,
            // With three or more plans, the middle one is highlighted as the usual choice
            'recommendedId' => $plans->count() >= 3 ? $plans[intdiv($plans->count(), 2)]->id : null,
            'hasYearly' => $plans->contains(fn (Plan $plan) => $plan->price_yearly > 0),
        ]);
    }
}
