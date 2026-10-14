<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Selects the business for this request from its host, before the session and sign-in are read.
 * Does nothing while tenancy is off.
 */
class IdentifyTenant
{
    public function __construct(private TenantResolver $resolver, private TenantManager $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tenancy.enabled')) {
            return $next($request);
        }

        $host = $request->getHost();

        // The platform's own pages (sign-up, admin) arrive in M4; business pages never open here
        abort_if($this->resolver->isCentral($host), 404);

        $tenant = $this->resolver->forHost($host);
        abort_unless($tenant, 404, 'Workspace not found.');
        abort_unless($tenant->isUsable(), 403, 'This workspace is not available. Please contact support.');

        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
