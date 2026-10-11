<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Business details saved on the Settings page replace the config defaults
        $this->app->make(Settings::class)->apply();

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
