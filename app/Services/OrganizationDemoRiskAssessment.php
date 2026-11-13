<?php

namespace App\Services;

use App\Models\OrganizationDemoRequest;
use Illuminate\Support\Str;

class OrganizationDemoRiskAssessment
{
    /** @return array{email_domain_hash: string, risk_flags: list<string>, possible_duplicate_of_id: ?int} */
    public function assess(string $email): array
    {
        $email = Str::lower(trim($email));
        $domain = mb_strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        $domainHash = hash('sha256', $domain);
        $publicDomains = array_map('mb_strtolower', config('organization_demo.public_email_domains', []));
        $isPublicProvider = in_array($domain, $publicDomains, true);

        $sameEmail = OrganizationDemoRequest::query()
            ->where('business_email', $email)
            ->latest('id')
            ->first();
        $sameDomain = $isPublicProvider ? null : OrganizationDemoRequest::query()
            ->where('email_domain_hash', $domainHash)
            ->where('business_email', '<>', $email)
            ->latest('id')
            ->first();

        $flags = [];
        if ($isPublicProvider) {
            $flags[] = 'public_email_provider';
        }
        if ($sameEmail) {
            $flags[] = 'email_seen_before';
        }
        if ($sameDomain) {
            $flags[] = 'organization_domain_seen';
        }

        return [
            'email_domain_hash' => $domainHash,
            'risk_flags' => $flags,
            'possible_duplicate_of_id' => $sameEmail?->id ?? $sameDomain?->id,
        ];
    }
}
