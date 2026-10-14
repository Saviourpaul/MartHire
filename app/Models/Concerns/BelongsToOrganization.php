<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            $context = app(OrganizationContext::class);
            $parentOrganizationId = $model->parentOrganizationId();
            $model->organization_id ??= $parentOrganizationId ?? $context->organizationId();

            if ($model->organization_id === null) {
                throw new LogicException('An organization is required for this record.');
            }

            if ($parentOrganizationId !== null && (int) $model->organization_id !== $parentOrganizationId) {
                throw new LogicException('Related records must belong to the same organization.');
            }

            if (! $context->allowsUnscopedAccess() && $context->organizationId() !== null
                && (int) $model->organization_id !== $context->organizationId()) {
                throw new LogicException('This record does not belong to the current organization.');
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('organization_id')) {
                throw new LogicException('Organization ownership cannot be changed.');
            }

            $parentOrganizationId = $model->parentOrganizationId();

            if ($parentOrganizationId !== null && (int) $model->organization_id !== $parentOrganizationId) {
                throw new LogicException('Related records must belong to the same organization.');
            }

            $context = app(OrganizationContext::class);

            if (! $context->allowsUnscopedAccess() && $context->organizationId() !== null
                && (int) $model->organization_id !== $context->organizationId()) {
                throw new LogicException('This record does not belong to the current organization.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function parentOrganizationId(): ?int
    {
        return null;
    }
}
