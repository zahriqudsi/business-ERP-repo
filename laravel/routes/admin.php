<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform admin area, on the admin domain (tenancy only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest:platform')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('admin.login.store');
});

Route::middleware('auth:platform')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/', [TenantController::class, 'index'])->name('admin.tenants.index');
    Route::post('businesses/{tenant}/suspend', [TenantController::class, 'suspend'])->name('admin.tenants.suspend');
    Route::post('businesses/{tenant}/resume', [TenantController::class, 'resume'])->name('admin.tenants.resume');
    Route::put('businesses/{tenant}/plan', [TenantController::class, 'plan'])->name('admin.tenants.plan');
    Route::post('businesses/{tenant}/sign-in', [TenantController::class, 'impersonate'])->name('admin.tenants.impersonate');
});
