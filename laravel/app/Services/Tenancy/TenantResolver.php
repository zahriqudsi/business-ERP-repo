<?php

namespace App\Services\Tenancy;

use App\Models\Landlord\Domain;
use App\Models\Landlord\Tenant;
use Illuminate\Support\Str;

/**
 * Which business a request's host belongs to.
 */
class TenantResolver
{
    /**
     * Hosts of the platform itself, which never open a business.
     */
    public function isCentral(string $host): bool
    {
        return in_array(Str::lower($host), array_map(Str::lower(...), config('tenancy.central_domains')), true);
    }

    /**
     * "acme.{base_domain}" finds the business with slug "acme"; any other host is looked up as a custom domain.
     */
    public function forHost(string $host): ?Tenant
    {
        $host = Str::lower($host);
        $suffix = '.'.Str::lower((string) config('tenancy.base_domain'));

        if (str_ends_with($host, $suffix)) {
            $slug = substr($host, 0, -strlen($suffix));

            return preg_match(Tenant::SLUG_PATTERN, $slug) ? Tenant::where('slug', $slug)->first() : null;
        }

        return Domain::with('tenant')->where('domain', $host)->first()?->tenant;
    }
}
