<?php

namespace App\Providers;

use App\Models\ApplicationForm;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\PlatformSetting;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\CountryDisplayService;
use App\Services\JobCountryService;
use App\Services\NotificationInboxService;
use App\Services\PhoneNumberService;
use App\Services\PlatformSettingsService;
use App\Services\UserPreferencesService;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class);
        $this->app->scoped(JobCountryService::class);
        $this->app->scoped(CountryDisplayService::class);
        $this->app->scoped(PhoneNumberService::class);
        $this->app->scoped(PlatformSettingsService::class);
        $this->app->scoped(UserPreferencesService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function (\Illuminate\View\View $view): void {
            $view->with('platformBrand', app(PlatformSettingsService::class)->presentation());
        });

        // Apply the admin-configured mail sender identity over the .env default.
        // Read directly (not via PlatformSettingsService) so the service's
        // per-request cache is not pre-populated here, which would make the
        // shared platformBrand presentation stale within a request lifecycle.
        try {
            $mailFrom = PlatformSetting::query()->find(1, ['mail_from_name', 'mail_from_address']);
            if ($mailFrom && filled($mailFrom->mail_from_address)) {
                config(['mail.from.address' => $mailFrom->mail_from_address]);
            }
            if ($mailFrom && filled($mailFrom->mail_from_name)) {
                config(['mail.from.name' => $mailFrom->mail_from_name]);
            }
        } catch (\Throwable) {
            // Settings table not ready (e.g. during migrations); keep .env defaults.
        }

        View::composer('interviews._details', function (\Illuminate\View\View $view): void {
            $user = request()->user();
            $view->with('preferredInterviewDisplayTimezone', $user ? app(UserPreferencesService::class)->forUser($user)->timezone : null);
        });

        Gate::define('send_candidate_email', fn (User $user, ApplicationForm $application): bool => app(OrganizationPermissions::class)->can($user, 'send_candidate_email', application: $application));
        Gate::define('manage_email_templates', fn (User $user, Organization $organization): bool => $organization->id === app(OrganizationContext::class)->organizationId()
            && app(OrganizationPermissions::class)->can($user, 'manage_email_templates'));

        RateLimiter::for('candidate-email', fn (Request $request): array => [
            Limit::perHour((int) config('interviews.user_send_limit', 20))->by('interview-user:'.$request->user()?->id),
            Limit::perHour((int) config('interviews.org_send_limit', 100))->by('interview-organization:'.app(OrganizationContext::class)->organizationId()),
        ]);

        View::composer('components.layout', function (\Illuminate\View\View $view): void {
            $context = app(OrganizationContext::class);
            $user = request()->user();
            $memberships = $user?->isOrganization()
                ? OrganizationMember::withoutGlobalScope(OrganizationScope::class)->with('organization')
                    ->where('user_id', $user->id)->where('status', 'active')->orderBy('id')->get()
                : collect();
            $view->with([
                'currentUser' => $user,
                'currentOrganization' => $context->organization(),
                'organizationMemberships' => $memberships,
                'currentOrganizationRole' => $memberships->firstWhere('organization_id', $context->organizationId())?->role,
                ...($user && $user->hasVerifiedEmail() && $user->isActive()
                    ? app(NotificationInboxService::class)->summary($user)
                    : ['recentNotifications' => collect(), 'unreadNotificationCount' => 0]),
            ]);
        });

        RateLimiter::for('team-invitations', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('downloads', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        // Abuse-prevention limiters (login, registration, password reset,
        // verification, invitation, confirmation, applications) are defined in
        // App\Support\RateLimiting\AbuseGuard via RateLimitServiceProvider.

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Your '.app(PlatformSettingsService::class)->current()->display_name.' Password')
                ->markdown('emails.auth.reset-password', [
                    'user' => $notifiable,
                    'url' => $url,
                    'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                ]);
        });
    }
}
