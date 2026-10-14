<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ties a session to the business it was started on.
 *
 * Sessions only store a user id, and every business has a user #1. A session cookie
 * copied from a.example.com to b.example.com would otherwise sign in as b's user #1
 * (or carry a half-finished OTP sign-in across), so a mismatched session is thrown away.
 */
class BindSessionToTenant
{
    private const SESSION_KEY = 'tenant_id';

    public function __construct(private TenantManager $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenancy->current();
        if (! $tenant) {
            return $next($request);
        }

        $session = $request->session();
        $boundTo = $session->get(self::SESSION_KEY);

        if ($boundTo !== null && $boundTo !== $tenant->getKey()) {
            $session->invalidate();
            Auth::forgetGuards();
        }

        $session->put(self::SESSION_KEY, $tenant->getKey());

        return $next($request);
    }
}
