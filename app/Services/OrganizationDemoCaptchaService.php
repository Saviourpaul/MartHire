<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class OrganizationDemoCaptchaService
{
    public function enabled(): bool
    {
        return (bool) config('organization_demo.captcha.enabled');
    }

    public function configured(): bool
    {
        return filled(config('organization_demo.captcha.site_key'))
            && filled(config('organization_demo.captcha.secret_key'));
    }

    public function siteKey(): ?string
    {
        return $this->enabled() && $this->configured()
            ? (string) config('organization_demo.captcha.site_key')
            : null;
    }

    public function verify(?string $token, Request $request): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! $this->configured() || ! filled($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout((int) config('organization_demo.captcha.timeout_seconds', 5))
                ->post((string) config('organization_demo.captcha.verify_url'), [
                    'secret' => config('organization_demo.captcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            if (! $response->successful() || $response->json('success') !== true) {
                return false;
            }

            $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $responseHost = $response->json('hostname');

            return filled($expectedHost) && is_string($responseHost) && filled($responseHost)
                && hash_equals(mb_strtolower((string) $expectedHost), mb_strtolower($responseHost));
        } catch (Throwable) {
            return false;
        }
    }
}
