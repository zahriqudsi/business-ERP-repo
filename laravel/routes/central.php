<?php

use App\Http\Controllers\Central\FindWorkspaceController;
use App\Http\Controllers\Central\HomeController;
use App\Http\Controllers\Central\PricingController;
use App\Http\Controllers\Central\SignupController;
use App\Http\Controllers\Central\SolutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The platform's own pages, on its main domain (tenancy only)
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('central.home');

Route::get('signup', [SignupController::class, 'create'])->name('central.signup');
Route::post('signup', [SignupController::class, 'store'])->middleware('throttle:5,60')->name('central.signup.store');

Route::view('features', 'central.features')->name('central.features');
Route::get('solutions/{solution}', SolutionController::class)
    ->whereIn('solution', array_keys(SolutionController::SOLUTIONS))->name('central.solutions');
Route::get('pricing', PricingController::class)->name('central.pricing');
Route::view('contact', 'central.contact')->name('central.contact');

Route::view('terms', 'central.terms')->name('central.terms');
Route::view('privacy', 'central.privacy')->name('central.privacy');

Route::get('find', [FindWorkspaceController::class, 'create'])->name('central.find');
Route::post('find', [FindWorkspaceController::class, 'store'])->middleware('throttle:5,10')->name('central.find.send');
