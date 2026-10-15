<?php

/*
|--------------------------------------------------------------------------
| Multi-tenancy
|--------------------------------------------------------------------------
|
| Each business (tenant) has its own database. The request's host picks the
| business: "{slug}.{base_domain}", or a custom domain from the landlord
| `domains` table. Off, the app runs as a single business on DB_DATABASE.
|
*/

return [

    'enabled' => (bool) env('TENANCY_ENABLED', false),

    // Business subdomains live under this domain: acme.yourapp.com
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'localhost'),

    // Hosts that serve the platform itself, never a business. The first one hosts the landing,
    // sign-up and "find my workspace" pages; the others redirect to it.
    'central_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'localhost,127.0.0.1'))))),

    // The platform admin area, for platform staff only
    'admin_domain' => env('TENANCY_ADMIN_DOMAIN', 'admin.'.env('TENANCY_BASE_DOMAIN', 'localhost')),

    // The landlord connection; it also runs CREATE DATABASE, so it needs that right
    'landlord_connection' => 'landlord',

    // A business's database is named prefix + its id, never its (changeable) subdomain
    'database_prefix' => env('TENANCY_DATABASE_PREFIX', 'tenant_'),

    'billing' => [
        // Days a business keeps working after its trial or paid time ends, before it is paused
        'grace_days' => (int) env('TENANCY_BILLING_GRACE_DAYS', 7),
        // How long a closed business's data is kept before it may be deleted
        'retention_days' => (int) env('TENANCY_RETENTION_DAYS', 90),
        // The plan (by code) new sign-ups start their trial on; none = no limits
        'signup_plan' => env('TENANCY_SIGNUP_PLAN'),
        // Shown to owners on their Plan & billing page: how to pay (bank details etc.)
        'payment_instructions' => env('TENANCY_PAYMENT_INSTRUCTIONS', ''),
    ],

    // Subdomains a business can't take
    'reserved_slugs' => ['www', 'admin', 'api', 'app', 'mail', 'status', 'help', 'support', 'billing', 'static', 'assets'],

];
