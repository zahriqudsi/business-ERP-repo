<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use Illuminate\View\View;

/**
 * The platform's landing page.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('central.home', [
            // The cheapest paid plan on sale, for "plans from …"
            'startingPlan' => Plan::where('is_active', true)->where('is_public', true)->where('price_monthly', '>', 0)->orderBy('price_monthly')->first(),
        ]);
    }
}
