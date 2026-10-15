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

        // Platform hosts have their own routes (routes/central.php); business pages never open there
        abort_if($this->resolver->isCentral($host), 404);

        $tenant = $this->resolver->forHost($host);
        abort_unless($tenant, 404, 'Workspace not found.');

        if (! $tenant->isUsable()) {
            return response()->view('tenancy.unavailable', ['tenant' => $tenant], $tenant->status === 'provisioning' ? 503 : 403);
        }

        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
