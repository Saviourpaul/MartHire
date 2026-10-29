<?php

namespace App\Models;

use App\Enums\InterviewTemplateType;
use App\Models\Scopes\OrganizationScope;
use App\Support\EmailTemplateBuilder;
use App\Support\OrganizationContext;
use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'type', 'name', 'subject', 'body', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['type' => InterviewTemplateType::class, 'is_active' => 'boolean', 'is_system' => 'boolean'];
    }

    public function newEloquentBuilder(mixed $query): EmailTemplateBuilder
    {
        return new EmailTemplateBuilder($query);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (EmailTemplate $template): void {
            if ($template->is_system || $template->system_key !== null || $template->organization_id === null) {
                throw new LogicException('System email templates are installed by migrations and cannot be created here.');
            }

            $context = app(OrganizationContext::class);

            if (! $context->allowsUnscopedAccess() && $context->organizationId() !== $template->organization_id) {
                throw new LogicException('This template does not belong to the current organization.');
            }
        });

        static::updating(function (EmailTemplate $template): void {
            // Ownership, type, and system markers are immutable for every template.
            if ($template->isDirty(['organization_id', 'type', 'is_system', 'system_key'])) {
                throw new LogicException('System templates and template ownership cannot be changed.');
            }

            // System templates allow content-only edits (subject/body) by admins and
            // are not org-scoped. Organization templates stay scoped to their owner.
            if (! $template->getOriginal('is_system')) {
                $context = app(OrganizationContext::class);

                if (! $context->allowsUnscopedAccess() && $context->organizationId() !== $template->organization_id) {
                    throw new LogicException('This template does not belong to the current organization.');
                }
            }
        });

        static::deleting(function (EmailTemplate $template): void {
            if ($template->is_system) {
                throw new LogicException('System email templates cannot be deleted.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
