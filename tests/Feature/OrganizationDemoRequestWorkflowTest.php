<?php

use App\Enums\OrganizationDemoOnboardingStatus;
use App\Enums\OrganizationDemoRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationDemoRequest;
use App\Models\User;
use App\Notifications\OrganizationDemoRequestEmail;
use App\Notifications\OrganizationDemoSetupInvitation;
use App\Services\OrganizationDemoCaptchaService;
use App\Services\WorkflowNotificationService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

function workflowDemoRequest(array $overrides = []): OrganizationDemoRequest
{
    $email = $overrides['business_email'] ?? 'owner@sample-company.test';

    return OrganizationDemoRequest::query()->create(array_merge([
        'contact_first_name' => 'Morgan',
        'contact_last_name' => 'Owner',
        'company_name' => 'Sample Company',
        'business_email' => $email,
        'active_email_hash' => hash('sha256', strtolower($email)),
        'phone' => '+12025550123',
        'phone_country' => 'US',
        'phone_dial_code' => '+1',
        'phone_national_number' => '2025550123',
        'industry' => null,
        'organization_size' => '11-50',
        'status' => OrganizationDemoRequestStatus::Submitted,
        'onboarding_status' => OrganizationDemoOnboardingStatus::NotStarted,
    ], $overrides));
}

function verifiedDemoAdmin(): User
{
    return User::factory()->admin()->create(['status' => UserStatus::Active]);
}

function validDemoSubmission(array $overrides = []): array
{
    return array_merge([
        'contact_first_name' => 'Morgan',
        'contact_last_name' => 'Owner',
        'company_name' => 'Sample Company',
        'business_email' => 'morgan@gmail.com',
        'phone' => '+12025550123',
        'phone_country' => 'US',
        'organization_size' => '11-50',
    ], $overrides);
}

test('admin demo request review opens as a full page and is restricted to authorized administrators', function () {
    $admin = verifiedDemoAdmin();
    $request = workflowDemoRequest();

    $this->actingAs($admin);
    $index = $this->get(route('admin.demo-requests.index'))->assertOk();
    $reviewUrl = route('admin.demo-requests.show', $request);
    preg_match('/<a\\b[^>]*href="'.preg_quote($reviewUrl, '/').'"[^>]*>.*?Review.*?<\\/a>/s', $index->getContent(), $reviewLink);

    expect($reviewLink)->not->toBeEmpty()
        ->and($reviewLink[0])->not->toContain('data-live-link');

    $this->get($reviewUrl)->assertOk()->assertSee('Sample Company');

    $this->actingAs(User::factory()->organization()->create())
        ->get($reviewUrl)
        ->assertForbidden();
});

test('demo submission uses a honeypot and records public email risk without rejecting Gmail or duplicating notifications', function () {
    Notification::fake();
    verifiedDemoAdmin();

    $this->from(route('organization-demo.create'))
        ->post(route('organization-demo.store'), validDemoSubmission(['website' => 'https://spam.example']))
        ->assertRedirect(route('organization-demo.create'))
        ->assertSessionHasErrors('website');

    $this->assertDatabaseCount('organization_demo_requests', 0);

    $this->post(route('organization-demo.store'), validDemoSubmission())
        ->assertRedirect(route('organization-demo.create'))
        ->assertSessionHas('demo-request-submitted');

    $request = OrganizationDemoRequest::query()->sole();
    expect($request->business_email)->toBe('morgan@gmail.com')
        ->and($request->risk_flags)->toContain('public_email_provider')
        ->and($request->industry)->toBeNull();

    $this->post(route('organization-demo.store'), validDemoSubmission(['company_name' => 'Another Company']))
        ->assertRedirect(route('organization-demo.create'));

    expect(OrganizationDemoRequest::query()->count())->toBe(1)
        ->and($request->fresh()->risk_flags)->toContain('repeat_submission');

    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseCount('notification_email_outbox', 1);
    Notification::assertSentOnDemandTimes(OrganizationDemoRequestEmail::class, 1);
});

test('admin notification failures do not lose requester confirmation and can be retried idempotently', function () {
    Notification::fake();
    $workflowNotifications = Mockery::mock(WorkflowNotificationService::class);
    $workflowNotifications->shouldReceive('organizationDemoRequestSubmitted')
        ->twice()
        ->andThrow(new RuntimeException('Notification store is temporarily unavailable.'));
    $this->app->instance(WorkflowNotificationService::class, $workflowNotifications);

    $this->post(route('organization-demo.store'), validDemoSubmission())
        ->assertRedirect(route('organization-demo.create'))
        ->assertSessionHas('demo-request-submitted');
    $this->post(route('organization-demo.store'), validDemoSubmission())
        ->assertRedirect(route('organization-demo.create'))
        ->assertSessionHas('demo-request-submitted');

    $this->assertDatabaseCount('organization_demo_requests', 1);
    Notification::assertSentOnDemandTimes(OrganizationDemoRequestEmail::class, 1);
});

test('admin guided demo, information request, and decline actions update status and append audit events', function () {
    Notification::fake();
    config(['organization_demo.booking_url' => 'https://calendar.example.test/marthire-demo']);
    $admin = verifiedDemoAdmin();
    $guided = workflowDemoRequest(['business_email' => 'guided@sample-company.test']);

    $this->actingAs($admin)
        ->post(route('admin.demo-requests.guided-demo', $guided))
        ->assertRedirect();

    expect($guided->fresh()->status)->toBe(OrganizationDemoRequestStatus::GuidedDemoOffered)
        ->and($guided->events()->where('event', 'guided_demo_offered')->exists())->toBeTrue();

    $information = workflowDemoRequest(['business_email' => 'information@sample-company.test']);
    $this->post(route('admin.demo-requests.request-information', $information), [
        'message' => 'Please share the number of hiring team members who need access.',
    ])->assertRedirect();

    expect($information->fresh()->status)->toBe(OrganizationDemoRequestStatus::InformationRequested)
        ->and($information->events()->where('event', 'information_requested')->exists())->toBeTrue();

    $declined = workflowDemoRequest(['business_email' => 'declined@sample-company.test']);
    $this->post(route('admin.demo-requests.reject', $declined), ['reason' => 'The request could not be verified.'])
        ->assertRedirect();

    expect($declined->fresh()->status)->toBe(OrganizationDemoRequestStatus::Rejected)
        ->and($declined->fresh()->rejection_reason)->toBe('The request could not be verified.')
        ->and($declined->events()->where('event', 'rejected')->exists())->toBeTrue();

    Notification::assertSentOnDemandTimes(OrganizationDemoRequestEmail::class, 3);
});

test('requester information links are signed, expire, and can only be used once', function () {
    $token = Str::random(64);
    $expiresAt = now()->addDay();
    $request = workflowDemoRequest([
        'status' => OrganizationDemoRequestStatus::InformationRequested,
        'requester_update_token_hash' => hash('sha256', $token),
        'requester_update_expires_at' => $expiresAt,
    ]);
    $signedUrl = URL::temporarySignedRoute('organization-demo.followup.show', $expiresAt, ['token' => $token]);
    $unsignedUrl = route('organization-demo.followup.show', ['token' => $token]);

    $this->get($unsignedUrl)->assertForbidden();
    $this->get($signedUrl)->assertOk()->assertSee('Provide more information');
    $this->post($signedUrl, ['response' => 'We have 14 hiring team members.'])->assertOk();

    expect($request->fresh()->status)->toBe(OrganizationDemoRequestStatus::UnderReview)
        ->and($request->fresh()->requester_update_token_hash)->toBeNull()
        ->and($request->events()->where('event', 'requester_information_received')->exists())->toBeTrue();

    $this->get($signedUrl)->assertNotFound();
});

test('trial approval creates a hashed expiring invitation and queues secure setup mail', function () {
    Notification::fake();
    $admin = verifiedDemoAdmin();
    $request = workflowDemoRequest();

    $this->actingAs($admin)
        ->post(route('admin.demo-requests.approve', $request))
        ->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe(OrganizationDemoRequestStatus::Approved)
        ->and($request->setup_signed_at)->not->toBeNull()
        ->and($request->setup_expires_at->betweenIncluded(now()->addDays(6), now()->addDays(8)))->toBeTrue()
        ->and(strlen((string) $request->setup_token_hash))->toBe(64)
        ->and($request->setup_token_hash)->not->toBe('');

    $this->assertDatabaseHas('organization_demo_request_events', [
        'organization_demo_request_id' => $request->id,
        'event' => 'approved',
    ]);
    Notification::assertSentOnDemand(OrganizationDemoSetupInvitation::class);

    $invitation = new OrganizationDemoSetupInvitation('https://example.test/setup', 'Morgan Owner', 'Sample Company', 7);
    expect($invitation)->toBeInstanceOf(ShouldQueue::class)
        ->and($invitation)->toBeInstanceOf(ShouldBeEncrypted::class);
});

test('an invitation without a valid expiry is marked expired and cannot open the setup form', function () {
    $token = Str::random(64);
    $request = workflowDemoRequest([
        'status' => OrganizationDemoRequestStatus::Approved,
        'onboarding_status' => OrganizationDemoOnboardingStatus::InvitationSent,
        'setup_token_hash' => hash('sha256', $token),
        'setup_expires_at' => null,
    ]);

    $this->get(route('organization-demo.setup.show', ['token' => $token]))->assertGone();

    expect($request->fresh()->onboarding_status)->toBe(OrganizationDemoOnboardingStatus::InvitationExpired)
        ->and($request->events()->where('event', 'setup_invitation_expired')->exists())->toBeTrue();
});

test('enabled CAPTCHA verification fails closed when the response host does not match the application', function () {
    $verifyUrl = 'https://captcha.example.test/verify';
    config([
        'app.url' => 'https://marthire.example.test',
        'organization_demo.captcha.enabled' => true,
        'organization_demo.captcha.site_key' => 'public-test-key',
        'organization_demo.captcha.secret_key' => 'private-test-key',
        'organization_demo.captcha.verify_url' => $verifyUrl,
    ]);

    $responseHosts = ['attacker.example.test', 'marthire.example.test'];
    Http::fake(function () use (&$responseHosts) {
        return Http::response(['success' => true, 'hostname' => array_shift($responseHosts)]);
    });
    expect(app(OrganizationDemoCaptchaService::class)
        ->verify('captcha-response', HttpRequest::create('/request-demo', 'POST')))->toBeFalse();

    expect(app(OrganizationDemoCaptchaService::class)
        ->verify('captcha-response', HttpRequest::create('/request-demo', 'POST')))->toBeTrue();
});

test('existing applicant self-registration remains available alongside the demo request flow', function () {
    $this->post(route('register'), [
        'first_name' => 'Taylor',
        'last_name' => 'Applicant',
        'email' => 'taylor.applicant@example.test',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'taylor.applicant@example.test',
        'role' => UserRole::Applicant->value,
        'status' => UserStatus::Pending->value,
    ]);
});
