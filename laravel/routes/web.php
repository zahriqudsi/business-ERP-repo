<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentEmailController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobNoteController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\OutstandingArController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorBillController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Record ids are numeric; this also stops e.g. /invoices/list_invoices.php matching invoices/{invoice}
Route::patterns(array_fill_keys(['invoice', 'quotation', 'job', 'purchase', 'bill', 'customer', 'supplier', 'product', 'user', 'id'], '[0-9]+'));

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware(['guest', 'cache.headers:no_store;private'])->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/verify-otp', [OtpController::class, 'create'])->name('otp.create');
    Route::post('/verify-otp', [OtpController::class, 'store'])->middleware('throttle:10,1')->name('otp.store');
    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])->middleware('throttle:5,1')->name('otp.resend');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('logo', LogoController::class)->name('logo');

    // Everyone signed in: day-to-day work (deleting and merging are below, for managers and owners)
    $resources = [
        'invoices' => InvoiceController::class,
        'quotations' => QuotationController::class,
        'jobs' => JobNoteController::class,
        'purchases' => PurchaseController::class,
        'customers' => CustomerController::class,
        'suppliers' => SupplierController::class,
        'products' => ProductController::class,
    ];
    foreach ($resources as $name => $controller) {
        Route::resource($name, $controller)->except('destroy');
    }
    Route::resource('bills', VendorBillController::class)->except(['show', 'destroy'])->parameters(['bills' => 'bill']);

    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('outstanding-ar', OutstandingArController::class)->name('outstanding-ar');
    Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    Route::get('jobs/{job}/pdf', [JobNoteController::class, 'pdf'])->name('jobs.pdf');
    Route::post('jobs/{job}/signature', [JobNoteController::class, 'sign'])->name('jobs.sign');
    Route::post('products/{product}/adjust', [ProductController::class, 'adjust'])->name('products.adjust');

    // Payments (received from customers / made to suppliers)
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/receive', [PaymentController::class, 'createReceived'])->name('payments.receive');
    Route::post('payments/receive', [PaymentController::class, 'storeReceived'])->name('payments.receive.store');
    Route::get('payments/make', [PaymentController::class, 'createMade'])->name('payments.make');
    Route::post('payments/make', [PaymentController::class, 'storeMade'])->name('payments.make.store');
    Route::put('payments/{type}/{id}', [PaymentController::class, 'update'])->whereIn('type', ['received', 'made'])->name('payments.update');

    // Email a document as a PDF
    Route::get('documents/{type}/{id}/email', [DocumentEmailController::class, 'create'])
        ->whereIn('type', DocumentEmailController::TYPES)->name('documents.email');
    Route::post('documents/{type}/{id}/email', [DocumentEmailController::class, 'send'])
        ->whereIn('type', DocumentEmailController::TYPES)->middleware('throttle:20,1')->name('documents.email.send');

    // My account
    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('account', [AccountController::class, 'update'])->name('account.update');

    // Managers and owners: delete and merge records
    Route::middleware('can:delete-records')->group(function () use ($resources) {
        foreach ($resources as $name => $controller) {
            Route::resource($name, $controller)->only('destroy');
        }
        Route::resource('bills', VendorBillController::class)->only('destroy')->parameters(['bills' => 'bill']);
        Route::delete('payments/{type}/{id}', [PaymentController::class, 'destroy'])->whereIn('type', ['received', 'made'])->name('payments.destroy');
        Route::post('customers/{customer}/merge', [CustomerController::class, 'merge'])->name('customers.merge');
        Route::post('suppliers/{supplier}/merge', [SupplierController::class, 'merge'])->name('suppliers.merge');
    });

    // Managers and owners: reports
    Route::middleware('can:view-reports')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });

    // Owners: running the business
    Route::middleware('can:manage-business')->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);

        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{file}', [BackupController::class, 'download'])->where('file', 'backup-[0-9_-]+\.sql(\.gz)?')->name('backups.download');
        Route::delete('backups/{file}', [BackupController::class, 'destroy'])->where('file', 'backup-[0-9_-]+\.sql(\.gz)?')->name('backups.destroy');

        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});

/*
|--------------------------------------------------------------------------
| Legacy URLs (old .php pages) -> new routes, so bookmarks keep working
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('/login.php', '/login');
Route::permanentRedirect('/invoices/list_invoices.php', '/invoices');
Route::permanentRedirect('/invoices/create_invoice.php', '/invoices/create');
Route::permanentRedirect('/invoices/outstanding_ar.php', '/outstanding-ar');
Route::permanentRedirect('/quotations/list_quotations.php', '/quotations');
Route::permanentRedirect('/quotations/create_quotation.php', '/quotations/create');
Route::permanentRedirect('/jobs/list_jobs.php', '/jobs');
Route::permanentRedirect('/jobs/create_job.php', '/jobs/create');
Route::permanentRedirect('/payments/list_payments.php', '/payments');
Route::permanentRedirect('/purchases/list_purchases.php', '/purchases');
Route::permanentRedirect('/bills/list_bills.php', '/bills');

$legacyIdRedirects = [
    '/invoices/view_invoice.php' => 'invoices.show',
    '/invoices/edit_invoice.php' => 'invoices.edit',
    '/generate_pdf.php' => 'invoices.pdf',
    '/quotations/view_quotation.php' => 'quotations.show',
    '/quotations/edit_quotation.php' => 'quotations.edit',
    '/generate_quotation_pdf.php' => 'quotations.pdf',
    '/jobs/view_job.php' => 'jobs.show',
    '/jobs/edit_job.php' => 'jobs.edit',
    '/jobs/job_pdf.php' => 'jobs.pdf',
    '/purchases/view_purchase.php' => 'purchases.show',
    '/purchases/edit_purchase.php' => 'purchases.edit',
    '/bills/edit_bill.php' => 'bills.edit',
];

foreach ($legacyIdRedirects as $legacyPath => $routeName) {
    Route::get($legacyPath, fn (Request $request) => redirect()->route($routeName, (int) $request->query('id'), 301));
}

Route::get('/payments/receive_payment.php', fn (Request $request) => redirect()->route('payments.receive', $request->query(), 301));
Route::get('/payments/make_payment.php', fn (Request $request) => redirect()->route('payments.make', $request->query(), 301));
