<?php

namespace App\Services;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationSetting;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OrganizationSettingsService
{
    /** @var list<string> */
    private const SETTING_FIELDS = [
        'about',
        'careers_url',
        'public_contact_email',
        'public_contact_phone',
        'default_interview_timezone',
        'default_interview_contact_name',
        'default_interview_contact_phone',
        'default_employment_type',
        'default_application_instructions',
    ];

    public function __construct(private OrganizationContext $context, private ActivityLogger $audit) {}

    public function forOrganization(Organization|int $organization): OrganizationSetting
    {
        $id = $organization instanceof Organization ? $organization->id : $organization;

        return OrganizationSetting::query()->where('organization_id', $id)->first()
            ?? new OrganizationSetting(['organization_id' => $id]);
    }

    /** @param array<string,mixed> $data */
    public function update(Organization $organization, User $actor, array $data): OrganizationSetting
    {
        $newLogoPath = null;

        try {
            if (($data['company_logo'] ?? null) instanceof UploadedFile) {
                $newLogoPath = $data['company_logo']->store('organization-branding', 'public') ?: null;
                abort_if($newLogoPath === null, 422, 'The logo could not be saved. Please try again.');
            }

            return DB::transaction(function () use ($organization, $actor, $data, $newLogoPath): OrganizationSetting {
                abort_unless($this->context->organizationId() === $organization->id, 409, 'Your active organization changed. Reload Settings before saving.');
                $locked = Organization::query()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
                $freshActor = User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
                $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                    ->where('organization_id', $locked->id)->where('user_id', $actor->id)->lockForUpdate()->first();
                abort_unless($freshActor->isOrganization() && $freshActor->isActive() && $freshActor->hasVerifiedEmail()
                    && $membership?->role === OrganizationRole::Owner && $membership->status === OrganizationMemberStatus::Active, 403);
                $settings = OrganizationSetting::query()->firstOrNew(['organization_id' => $locked->id]);
                abort_unless((int) $data['version'] === $settings->version, 409, 'These settings were changed by another request. Reload before saving.');

                $before = ['name' => $locked->name, ...$settings->only([...self::SETTING_FIELDS, 'company_logo_path'])];
                $locked->update(['name' => $data['name']]);
                $settings->fill(Arr::only($data, self::SETTING_FIELDS));

                $oldLogoPath = null;
                if ($newLogoPath !== null || ($data['remove_company_logo'] ?? false)) {
                    $oldLogoPath = $settings->company_logo_path;
                    $settings->company_logo_path = $newLogoPath;
                }

                $settings->version++;
                $settings->save();

                $this->audit->record($locked, null, $actor, 'organization_settings_updated', [
                    'before' => $before,
                    'after' => ['name' => $locked->name, ...$settings->only([...self::SETTING_FIELDS, 'company_logo_path'])],
                    'version' => $settings->version,
                ]);

                if ($oldLogoPath) {
                    DB::afterCommit(fn () => Storage::disk('public')->delete($oldLogoPath));
                }

                return $settings;
            });
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }
    }
}
