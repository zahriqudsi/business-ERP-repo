<?php

use App\Http\Middleware\BindSessionToTenant;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The business is picked first (sessions and users live in its database), and the session checked once started
        $middleware->web(append: [BindSessionToTenant::class], prepend: [IdentifyTenant::class]);
        // ...and before sign-in is checked, or a session from another business would already count as signed in
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: BindSessionToTenant::class);

        // The admin area (tenancy only) has its own sign-in page
        $isAdminArea = fn (Request $request) => config('tenancy.enabled') && $request->getHost() === config('tenancy.admin_domain');
        $middleware->redirectGuestsTo(fn (Request $request) => $isAdminArea($request) ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $isAdminArea($request) ? route('admin.tenants.index') : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
