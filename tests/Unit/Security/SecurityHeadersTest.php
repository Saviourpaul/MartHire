<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('adds baseline browser security headers without replacing stricter response headers', function () {
    $response = new Response;
    $response->headers->set('Referrer-Policy', 'no-referrer');
    $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");

    $result = (new SecurityHeaders)->handle(Request::create('/'), fn () => $response);

    expect($result->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($result->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($result->headers->get('Referrer-Policy'))->toBe('no-referrer')
        ->and($result->headers->get('Content-Security-Policy'))->toBe("default-src 'none'; sandbox");

    $ordinaryResponse = (new SecurityHeaders)->handle(Request::create('/'), fn () => new Response);

    expect($ordinaryResponse->headers->get('Content-Security-Policy'))->toBe("frame-ancestors 'self'");
});
