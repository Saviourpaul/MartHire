<?php

namespace App\Services;

use App\Enums\InterviewTemplateType;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmailTemplateService
{
    public function resolve(int $organizationId, InterviewTemplateType $type): EmailTemplate
    {
        return EmailTemplate::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)->where('type', $type->value)
            ->where('is_system', false)->where('is_active', true)->first()
            ?? EmailTemplate::withoutGlobalScope(OrganizationScope::class)
                ->whereNull('organization_id')->where('system_key', $type->value)
                ->where('is_system', true)->where('is_active', true)->firstOrFail();
    }

    /** @return Collection<int, EmailTemplate> */
    public function forOrganization(int $organizationId): Collection
    {
        return new Collection(array_map(fn (InterviewTemplateType $type): EmailTemplate => EmailTemplate::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)->where('type', $type->value)->where('is_system', false)->first()
            ?? $this->resolve($organizationId, $type), InterviewTemplateType::cases()));
    }

    /** @return Collection<int, EmailTemplate> */
    public function forSending(int $organizationId): Collection
    {
        return new Collection(array_map(fn (InterviewTemplateType $type): EmailTemplate => $this->resolve($organizationId, $type), InterviewTemplateType::cases()));
    }

    /** @param array{subject: string, body: string, is_active?: bool} $data */
    public function saveOverride(Organization $organization, User $actor, InterviewTemplateType $type, array $data): EmailTemplate
    {
        Gate::forUser($actor)->authorize('manage_email_templates', $organization);
        app(InterviewTemplateRenderer::class)->validate($type, $data['subject'], $data['body']);

        return DB::transaction(function () use ($organization, $actor, $type, $data): EmailTemplate {
            Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manage_email_templates', $organization);

            return app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $actor, $type, $data): EmailTemplate {
                $template = EmailTemplate::withoutGlobalScope(OrganizationScope::class)
                    ->firstOrNew(['organization_id' => $organization->id, 'type' => $type->value]);

                $template->fill([
                    'name' => $type->label(), 'subject' => $data['subject'], 'body' => $data['body'],
                    'is_active' => $data['is_active'] ?? true, 'updated_by' => $actor->id,
                ]);

                if (! $template->exists) {
                    $template->created_by = $actor->id;
                }

                $template->save();
                app(ActivityLogger::class)->record($organization, null, $actor, 'system', [
                    'action' => 'email_template_updated', 'email_template_id' => $template->id, 'template_type' => $type->value,
                ]);

                return $template;
            });
        });
    }
}
