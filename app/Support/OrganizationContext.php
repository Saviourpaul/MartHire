<?php

namespace App\Support;

use App\Models\Organization;
use Closure;

class OrganizationContext
{
    private ?Organization $organization = null;

    private bool $unscoped = false;

    public function set(?Organization $organization, bool $allowUnscopedAccess = false): void
    {
        $this->organization = $organization;
        $this->unscoped = $allowUnscopedAccess;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function organizationId(): ?int
    {
        return $this->organization?->getKey();
    }

    public function allowsUnscopedAccess(): bool
    {
        return $this->unscoped;
    }

    public function clear(): void
    {
        $this->set(null);
    }

    public function runInOrganization(Organization $organization, Closure $callback): mixed
    {
        $previous = $this->organization;
        $unscoped = $this->unscoped;
        $this->set($organization);

        try {
            return $callback();
        } finally {
            $this->set($previous, $unscoped);
        }
    }
}
