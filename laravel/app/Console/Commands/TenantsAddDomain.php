<?php

namespace App\Console\Commands;

use App\Models\Landlord\Domain;
use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Lets a business keep its own address (e.g. the old pos.sjtechnology.lk) besides its subdomain.
 */
class TenantsAddDomain extends Command
{
    protected $signature = 'tenants:add-domain {slug : The business}
        {domain : e.g. pos.sjtechnology.lk}
        {--primary : Make it the business\'s main address}';

    protected $description = 'Open a business on a custom domain as well as its subdomain';

    public function handle(): int
    {
        $tenant = Tenant::where('slug', $this->argument('slug'))->first();
        if (! $tenant) {
            $this->error("No business with the subdomain '{$this->argument('slug')}'.");

            return self::FAILURE;
        }

        $domain = Str::lower(trim((string) $this->argument('domain')));
        $validator = Validator::make(['domain' => $domain], [
            'domain' => ['required', 'max:255', 'regex:/^(?=.{1,255}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', 'unique:landlord.domains,domain'],
        ], [
            'domain.regex' => 'Give a domain name such as pos.example.com (no http:// or path).',
            'domain.unique' => 'That domain already opens a business.',
        ]);
        // The platform's own hosts and its subdomains are never custom domains
        $validator->after(function ($validator) use ($domain) {
            $base = Str::lower((string) config('tenancy.base_domain'));
            if (in_array($domain, array_map(Str::lower(...), config('tenancy.central_domains')), true) || $domain === $base || str_ends_with($domain, '.'.$base)) {
                $validator->errors()->add('domain', 'That address belongs to the platform itself.');
            }
        });

        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        if ($this->option('primary')) {
            $tenant->domains()->update(['is_primary' => false]);
        }
        Domain::create(['tenant_id' => $tenant->id, 'domain' => $domain, 'is_primary' => (bool) $this->option('primary')]);

        $this->info("{$domain} now opens '{$tenant->name}'. Point its DNS at this server.");

        return self::SUCCESS;
    }
}
