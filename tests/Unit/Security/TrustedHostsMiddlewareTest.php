<?php

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;

it('rejects an untrusted Host header before URL generation', function () {
    Request::setTrustedHosts([]);
    TrustHosts::at(['^nearbyworks\.test$'], subdomains: false);

    $application = Mockery::mock(ApplicationContract::class);
    $application->shouldReceive('environment')->with('local')->andReturnFalse();
    $application->shouldReceive('runningUnitTests')->andReturnFalse();

    try {
        $request = Request::create('http://attacker.test/forgot-password');
        (new TrustHosts($application))->handle($request, fn () => new Response);

        expect(fn () => $request->getHost())->toThrow(SuspiciousOperationException::class);
    } finally {
        Request::setTrustedHosts([]);
        TrustHosts::flushState();
    }
});
