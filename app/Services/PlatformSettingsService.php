<?php

namespace App\Services;

use App\Enums\SocialPlatform;
use App\Models\EmailTemplate;
use App\Models\PlatformSetting;
use App\Models\Scopes\OrganizationScope;
use App\Models\SettingsAuditEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlatformSettingsService
{
    private const PRODUCT_FIELDS = ['display_name', 'mail_from_name', 'mail_from_address', 'support_email', 'support_phone', 'support_address', 'support_url', 'privacy_url', 'terms_url', 'facebook_url', 'x_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url', 'public_registration_enabled'];

    private ?PlatformSetting $resolved = null;

    /** @var list<string> */
    private array $pendingUploads = [];

    public function current(): PlatformSetting
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }
        try {
            return $this->resolved = PlatformSetting::query()->find(1) ?? new PlatformSetting;
        } catch (QueryException) {
            return $this->resolved = new PlatformSetting;
        }
    }

    /** @return array<string,mixed> */
    public function presentation(): array
    {
        $settings = $this->current();
        $logo = $this->assetUrl($settings->site_logo_path ?? $settings->logo_light_path ?? $settings->logo_dark_path, 'assets/images/logo.png');

        return [
            ...$settings->only(self::PRODUCT_FIELDS),
            'support_url' => $settings->support_url ?? route('contact'),
            'logo_url' => $logo,
            'logo_light_url' => $logo,
            'logo_dark_url' => $logo,
            'icon_url' => $this->assetUrl($settings->icon_path, 'assets/images/favicon.ico'),
            'social_links' => $this->socialLinks($settings),
        ];
    }

    /** Must run inside the account-creation transaction. */
    public function lockRegistrationSettings(): PlatformSetting
    {
        try {
            $settings = PlatformSetting::query()->whereKey(1)->lockForUpdate()->first();
        } catch (QueryException) {
            abort(503, 'Registration is temporarily unavailable. Please try again later.');
        }
        abort_unless($settings, 503, 'Registration is temporarily unavailable. Please try again later.');

        return $settings;
    }

    /** Must run inside the account-creation transaction. */
    public function assertPublicRegistrationEnabled(): void
    {
        $settings = $this->lockRegistrationSettings();

        if (! $settings->public_registration_enabled) {
            throw ValidationException::withMessages(['email' => 'Public registration is currently paused. Existing users can still sign in.']);
        }
    }

    /** @param array<string,mixed> $data */
    public function update(User $actor, array $data): PlatformSetting
    {
        $newPaths = [];
        $oldPaths = [];

        try {
            foreach (['site_logo', 'icon'] as $asset) {
                if (($data[$asset] ?? null) instanceof UploadedFile) {
                    $path = $data[$asset]->store('platform-branding', 'public');
                    if (! $path) {
                        throw ValidationException::withMessages([$asset => 'The image could not be saved. Please try again.']);
                    }
                    $newPaths[$asset.'_path'] = $path;
                    $this->pendingUploads[] = $path;
                }
            }

            return DB::transaction(function () use ($actor, $data, $newPaths, &$oldPaths): PlatformSetting {
                $freshActor = User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
                abort_unless($freshActor->isAdmin() && $freshActor->isActive() && $freshActor->hasVerifiedEmail(), 403);
                $settings = PlatformSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                abort_unless((int) $data['version'] === $settings->version, 409, 'These settings were changed by another administrator. Reload before saving.');
                $before = $settings->only([...self::PRODUCT_FIELDS, 'site_logo_path', 'logo_light_path', 'logo_dark_path', 'icon_path']);
                $settings->fill(Arr::only($data, self::PRODUCT_FIELDS));
                $legacyPaths = array_filter([$settings->logo_light_path, $settings->logo_dark_path]);
                $settings->site_logo_path ??= $settings->logo_light_path ?? $settings->logo_dark_path;
                $settings->logo_light_path = null;
                $settings->logo_dark_path = null;
                foreach (['site_logo', 'icon'] as $asset) {
                    $column = $asset.'_path';
                    if (isset($newPaths[$column]) || ($data['remove_'.$asset] ?? false)) {
                        if ($settings->{$column}) {
                            $oldPaths[] = $settings->{$column};
                        }
                        $settings->{$column} = $newPaths[$column] ?? null;
                    }
                }
                if (isset($newPaths['site_logo_path']) || ($data['remove_site_logo'] ?? false)) {
                    $oldPaths = [...$oldPaths, ...$legacyPaths];
                }
                $settings->version++;
                $settings->updated_by = $actor->id;
                $settings->save();
                SettingsAuditEvent::create([
                    'actor_id' => $actor->id, 'scope' => 'platform', 'action' => 'platform_settings_updated',
                    'changes' => ['before' => $before, 'after' => $settings->only(array_keys($before)), 'version' => $settings->version],
                    'created_at' => now(),
                ]);
                DB::afterCommit(function () use (&$oldPaths): void {
                    $this->deleteUnreferencedUploads([...$oldPaths, ...$this->pendingUploads]);
                    $this->pendingUploads = [];
                });

                $this->resolved = null;

                return $settings;
            });
        } catch (Throwable $exception) {
            $this->deleteUnreferencedUploads(array_values($newPaths));
            $this->pendingUploads = array_values(array_diff($this->pendingUploads, $newPaths));
            throw $exception;
        }
    }

    /**
     * Content-only edit (subject/body) of a migration-installed system email
     * template by an administrator. Type, ownership, and system markers stay
     * immutable (enforced by the EmailTemplate model and builder guards).
     *
     * @param  array{subject: string, body: string}  $data
     */
    public function updateSystemEmailTemplate(User $actor, int $templateId, array $data): EmailTemplate
    {
        return DB::transaction(function () use ($actor, $templateId, $data): EmailTemplate {
            $freshActor = User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless($freshActor->isAdmin() && $freshActor->isActive() && $freshActor->hasVerifiedEmail(), 403);

            $template = EmailTemplate::withoutGlobalScope(OrganizationScope::class)
                ->where('is_system', true)->whereKey($templateId)->firstOrFail();

            $before = $template->only(['subject', 'body']);
            $template->fill(Arr::only($data, ['subject', 'body']));
            $template->updated_by = $actor->id;
            $template->save();

            SettingsAuditEvent::create([
                'actor_id' => $actor->id,
                'scope' => 'platform',
                'action' => 'system_email_template_updated',
                'changes' => ['template_id' => $template->id, 'before' => $before, 'after' => $template->only(['subject', 'body'])],
                'created_at' => now(),
            ]);

            return $template;
        });
    }

    public function discardPendingUploads(): void
    {
        $this->deleteUnreferencedUploads($this->pendingUploads);
        $this->pendingUploads = [];
        $this->resolved = null;
    }

    private function assetUrl(?string $path, string $fallback): string
    {
        return $path ? Storage::disk('public')->url($path) : asset($fallback);
    }

    /** @param list<string> $paths */
    private function deleteUnreferencedUploads(array $paths): void
    {
        if ($paths === []) {
            return;
        }
        try {
            $persisted = PlatformSetting::query()->find(1)?->only(['site_logo_path', 'logo_light_path', 'logo_dark_path', 'icon_path']) ?? [];
            Storage::disk('public')->delete(array_values(array_diff($paths, array_filter($persisted))));
        } catch (Throwable) {
            Log::warning('Platform branding cleanup could not complete.', ['file_count' => count($paths)]);
        }
    }

    /** @return list<array{key:string,label:string,url:string,icon:string}> */
    private function socialLinks(PlatformSetting $settings): array
    {
        $links = [];
        foreach (SocialPlatform::cases() as $platform) {
            $url = $settings->getAttribute($platform->value);
            if (filled($url)) {
                $links[] = ['key' => $platform->value, 'label' => $platform->label(), 'url' => $url, 'icon' => $platform->icon()];
            }
        }

        return $links;
    }
}
