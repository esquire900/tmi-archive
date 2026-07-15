<?php

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
        // Trust the reverse proxy (DDEV nginx / production load balancer) so
        // that request IPs used for metrics + rate limiting are the real client.
        $middleware->trustProxies(at: '*');

        // Soft global throttle across the public site to blunt crawl floods.
        $middleware->appendToGroup('web', \Illuminate\Routing\Middleware\ThrottleRequests::class.':web');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
