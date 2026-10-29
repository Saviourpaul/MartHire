<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAuthenticationVersion;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\InitializeOrganizationContext;
use App\Support\ExpirationResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureAuthenticationVersion::class, InitializeOrganizationContext::class]);
        $middleware->prependToPriorityList(
            before: ThrottleRequests::class,
            prepend: InitializeOrganizationContext::class,
        );
        $middleware->alias([
            'active.account' => EnsureAccountIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['code', 'current_password', 'password', 'password_confirmation']);

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return app(ExpirationResponse::class)->unauthenticated($request);
        });

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            return app(ExpirationResponse::class)->invalidSignature($request);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            $expiration = app(ExpirationResponse::class);
            $status = $exception->getStatusCode();

            if ($status === 410) {
                return $expiration->gone($request);
            }

            if ($status === 419) {
                return $expiration->session($request);
            }

            if (in_array($status, [403, 404], true) && $request->routeIs('storage.*')
                && $expiration->hasExpiredSignature($request)) {
                return $expiration->gone($request);
            }

            if ($status !== 403 || $request->expectsJson()) {
                return null;
            }

            return response()->view('errors.403', ['exception' => $exception], 403);
        });
    })->create();
