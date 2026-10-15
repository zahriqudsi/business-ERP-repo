<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * The platform's landing page.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('central.home');
    }
}
