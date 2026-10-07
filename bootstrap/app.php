<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.account' => EnsureAccountIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.419', [], 419);
        });

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->user()) {
                return redirect()
                    ->route('verification.notice')
                    ->withErrors(['verification' => 'This verification link is invalid or has expired. Request a new link.']);
            }

            return redirect()
                ->route('login')
                ->with('status', 'verification-link-invalid');
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($exception->getStatusCode() === 403
                && $request->route()?->getName() === 'verification.verify'
                && $request->user()) {
                return redirect()
                    ->route('verification.notice')
                    ->withErrors(['verification' => 'This verification link is invalid or belongs to a different account. Request a new link.']);
            }

            if ($exception->getStatusCode() !== 403) {
                return null;
            }

            return response()->view('errors.403', ['exception' => $exception], 403);
        });
    })->create();
