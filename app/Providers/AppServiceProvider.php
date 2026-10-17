<?php

namespace App\Providers;

use App\Models\ApplicationForm;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
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
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
            $memberships = $user?->isEmployer()
                ? OrganizationMember::withoutGlobalScope(OrganizationScope::class)->with('organization')
                    ->where('user_id', $user->id)->where('status', 'active')->orderBy('id')->get()
                : collect();
            $view->with([
                'currentOrganization' => $context->organization(),
                'organizationMemberships' => $memberships,
                'currentOrganizationRole' => $memberships->firstWhere('organization_id', $context->organizationId())?->role,
            ]);
        });

        RateLimiter::for('team-invitations', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('downloads', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('application-submit', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('verification-code', function (Request $request): array {
            $response = function (Request $request, array $headers): never {
                throw ValidationException::withMessages([
                    'code' => 'Too many verification attempts. Please wait before trying again.',
                ]);
            };

            return [
                Limit::perMinute((int) config('auth.verification.max_attempts', 5))
                    ->by('verification-user:'.$request->user()?->id)->response($response),
                Limit::perMinute((int) config('auth.verification.attempt_ip_limit', 20))
                    ->by('verification-ip:'.$request->ip())->response($response),
            ];
        });

        RateLimiter::for('verification-send', function (Request $request): array {
            $response = function (Request $request, array $headers): never {
                throw ValidationException::withMessages([
                    'verification' => 'Too many code requests. Please wait before requesting another code.',
                ]);
            };

            return [
                Limit::perHour((int) config('auth.verification.resend_limit', 6))
                    ->by('verification-send-user:'.$request->user()?->id)->response($response),
                Limit::perHour((int) config('auth.verification.resend_ip_limit', 20))
                    ->by('verification-send-ip:'.$request->ip())->response($response),
            ];
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Your MartHire Password')
                ->markdown('emails.auth.reset-password', [
                    'user' => $notifiable,
                    'url' => $url,
                    'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                ]);
        });
    }
}
