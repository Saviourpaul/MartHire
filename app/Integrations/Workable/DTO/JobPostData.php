<?php

namespace App\Integrations\Workable\DTO;

use App\Integrations\Workable\Exceptions\WorkableException;

final class JobPostData
{
    public function __construct(
        public readonly string $shortcode,
        public readonly string $externalId,
        public readonly string $title,
        public readonly ?string $fullTitle,
        public readonly string $state,
        public readonly ?string $department,
        public readonly ?string $workplaceType,
        public readonly ?string $url,
        public readonly ?string $applicationUrl,
        public readonly ?string $shortlink,
        public readonly array $location,
        public readonly array $locations,
        public readonly ?array $salary,
        public readonly array $keywords,
        public readonly ?string $contentHtml,
        public readonly ?\DateTimeImmutable $externalUpdatedAt,
    ) {}

    public static function fromApi(array $j): self
    {
        $shortcode = $j['shortcode'] ?? null;
        $title = $j['title'] ?? null;

        if (! is_string($shortcode) || $shortcode === '') {
            throw new WorkableException('Workable job is missing a shortcode.');
        }
        if (! is_string($title) || $title === '') {
            throw new WorkableException("Workable job {$shortcode} is missing a title.");
        }

        $parts = [];
        foreach (['full_description', 'requirements', 'benefits'] as $field) {
            $value = $j[$field] ?? null;
            if (is_string($value) && $value !== '') {
                $parts[] = $value;
            }
        }

        $updatedAt = null;
        if (! empty($j['updated_at']) && is_string($j['updated_at'])) {
            try {
                $updatedAt = new \DateTimeImmutable($j['updated_at']);
            } catch (\Throwable) {
                $updatedAt = null;
            }
        }

        return new self(
            shortcode: $shortcode,
            externalId: (string) ($j['id'] ?? ''),
            title: $title,
            fullTitle: $j['full_title'] ?? null,
            state: (string) ($j['state'] ?? ''),
            department: $j['department'] ?? null,
            workplaceType: $j['workplace_type'] ?? null,
            url: $j['url'] ?? null,
            applicationUrl: $j['application_url'] ?? null,
            shortlink: $j['shortlink'] ?? null,
            location: $j['location'] ?? [],
            locations: $j['locations'] ?? [],
            salary: $j['salary'] ?? null,
            keywords: $j['keywords'] ?? [],
            contentHtml: $parts === [] ? null : implode("\n", $parts),
            externalUpdatedAt: $updatedAt,
        );
    }
}
