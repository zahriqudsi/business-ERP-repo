<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Alerts;
use App\Services\Settings;
use App\Services\Tenancy\TenantAwareQueue;
use App\Services\Tenancy\TenantManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(TenantManager::class);
        $this->app->singleton(TenantAwareQueue::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Business details saved on the Settings page replace the config defaults.
        // With tenancy on, the business isn't known yet: TenantManager loads them per request.
        if (! config('tenancy.enabled')) {
            $this->app->make(Settings::class)->apply();
        }

        // Queued jobs run as the business they were queued in
        if (config('tenancy.enabled')) {
            $this->app->make(TenantAwareQueue::class)->register();
        }

        // A job that gave up (after its retries) is reported to the people who run the app
        Queue::failing(function (JobFailed $event) {
            $tenantId = $event->job->payload()['tenant_id'] ?? null;
            $this->app->make(Alerts::class)->send(
                'A background job failed',
                "Job: {$event->job->resolveName()}\n".($tenantId ? "Business: {$tenantId}\n" : '')."Error: {$event->exception->getMessage()}\n\nRetry with: php artisan queue:retry all",
            );
        });

        // /up (for an uptime monitor) also fails when a database can't be reached
        Event::listen(DiagnosingHealth::class, function () {
            DB::connection()->getPdo();
            if (config('tenancy.enabled')) {
                DB::connection(config('tenancy.landlord_connection'))->getPdo();
            }
        });

        // Roles: owners run the business (settings, users, backups, activity log);
        // managers can also delete records and see reports; staff do day-to-day work.
        Gate::define('manage-business', fn (User $user) => $user->isOwner());
        Gate::define('view-reports', fn (User $user) => $user->hasRole('owner', 'manager'));
        Gate::define('delete-records', fn (User $user) => $user->hasRole('owner', 'manager'));

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            ActivityLog::record('signed_in', $event->user->display_name.' signed in', 'user', $event->user->getKey());
        });

        // Name autocomplete on document forms
        View::composer(['invoices.form', 'quotations.form', 'jobs.form', 'payments.create'], function ($view) {
            $view->with('customerNames', Customer::namesForPicker());
        });
        View::composer(['invoices.form', 'quotations.form', 'purchases.form'], function ($view) {
            $view->with('productOptions', Product::forPicker());
        });
        View::composer('jobs.form', function ($view) {
            $view->with('customerContacts', Customer::query()
                ->where(fn ($q) => $q->whereNotNull('contact_person')->orWhereNotNull('phone')->orWhereNotNull('email'))
                ->get(['name', 'contact_person', 'phone', 'email'])
                ->keyBy(fn (Customer $customer) => mb_strtolower($customer->name)));
        });
        View::composer(['purchases.form', 'bills.form', 'payments.create'], function ($view) {
            $view->with('supplierNames', Supplier::namesForPicker());
        });
    }
}
