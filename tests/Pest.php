<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;
use App\Support\RateLimiting\AbuseGuard;
use App\Support\RateLimiting\RateLimitCacheRepository;
use Illuminate\Cache\RateLimiter as RateLimiterManager;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Tests\Support\UnavailableCacheStore;

/** @return array{0: Organization, 1: User} */
function organizationWithOwner(): array
{
    $owner = User::factory()->organization()->create();
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($owner);
    $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $owner->id)->firstOrFail();
    $membership->onboardingProgress()->firstOrFail()->forceFill([
        'current_step' => 'complete',
        'completed_steps' => ['welcome', 'profile', 'team', 'finish'],
        'completed_at' => now(),
    ])->save();

    return [$organization, $owner];
}

function addOrganizationMember(Organization $organization, OrganizationRole $role, OrganizationMemberStatus $status = OrganizationMemberStatus::Active): OrganizationMember
{
    $user = User::factory()->organization()->create();

    return app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $status,
    ]));
}

function useUnavailableRateLimitStore(): void
{
    app('cache')->extend('unavailable', function ($app, array $config) {
        return $app['cache']->repository(new UnavailableCacheStore, $config);
    });

    config([
        'cache.stores.unavailable' => ['driver' => 'unavailable'],
        'rate_limiting.store' => 'unavailable',
        'rate_limiting.fail_open' => true,
    ]);

    $app = app();
    $app->forgetInstance(RateLimitCacheRepository::class);
    $app->forgetInstance(AbuseGuard::class);
    $app->forgetInstance(RateLimiterManager::class);
    RateLimiterFacade::clearResolvedInstance(RateLimiterManager::class);

    UnavailableCacheStore::$attempts = 0;
    $app->make(AbuseGuard::class)->registerLimiters();
}
