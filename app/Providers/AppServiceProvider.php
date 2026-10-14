<?php

namespace App\Providers;

use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify Your Email Address')
                ->markdown('emails.auth.verify', [
                    'user' => $notifiable,
                    'url' => $url,
                    'expires' => config('auth.verification.expire', 60),

                ]);

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
