<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SocialPlatform;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Http\Requests\UpdateSystemEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\NotificationEmailOutbox;
use App\Models\Scopes\OrganizationScope;
use App\Services\PlatformSettingsService;
use App\Support\OrganizationPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PlatformSettingsController extends Controller
{
    public function __construct(private PlatformSettingsService $settings) {}

    public function edit(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('settings.platform', ['platformSettings' => $this->settings->current(), 'socialPlatforms' => SocialPlatform::cases()]);
    }

    public function update(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        try {
            $this->settings->update($request->user(), $request->validated());
        } catch (Throwable $exception) {
            $this->settings->discardPendingUploads();
            throw $exception;
        }

        return to_route('admin.settings.edit')->with('success', 'Platform settings saved.');
    }

    public function mail(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('settings.mail', ['diagnostics' => [
            'transport' => config('mail.default'),
            'from_name' => config('mail.from.name'), 'from_address' => config('mail.from.address'),
            'queue' => config('queue.default'), 'delivery_tested' => false,
            'outbox_pending' => NotificationEmailOutbox::query()->whereIn('status', ['pending', 'processing'])->count(),
            'outbox_failed' => NotificationEmailOutbox::query()->where('status', 'failed')->count(),
        ]]);
    }

    public function permissions(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('settings.permissions', ['globalRoles' => UserRole::cases(), 'organizationPermissions' => OrganizationPermissions::ROLE_PERMISSIONS]);
    }

    public function templates(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('settings.templates', ['templates' => EmailTemplate::withoutGlobalScope(OrganizationScope::class)->where('is_system', true)->orderBy('name')->get()]);
    }

    public function updateTemplate(UpdateSystemEmailTemplateRequest $request, int $template): RedirectResponse
    {
        $this->settings->updateSystemEmailTemplate($request->user(), $template, $request->validated());

        return to_route('admin.settings.templates')->with('success', 'Email template updated.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin() && $request->user()->isActive() && $request->user()->hasVerifiedEmail(), 403);
    }
}
