<?php

use App\Enums\JobStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Job;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationOnboardingProgress;
use App\Models\OrganizationSetting;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\OrganizationOnboardingService;
use App\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function onboardingJob(Organization $organization, User $owner, string $title, JobStatus $status = JobStatus::Open, ?string $dueDate = null): Job
{
    return app(OrganizationContext::class)->runInOrganization($organization, fn () => Job::create([
        'organization_id' => $organization->id,
        'employer_id' => $owner->id,
        'title' => $title,
        'description' => '<p>Role description</p>',
        'company' => $organization->name,
        'category' => 'Engineering',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'start_date' => today()->toDateString(),
        'due_date' => $dueDate ?? today()->addDays(30)->toDateString(),
        'status' => $status,
    ]));
}

/** @return array{Organization, User} */
function freshOrganizationOnboardingOwner(): array
{
    [$organization, $owner] = organizationWithOwner();
    $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $owner->id)->firstOrFail();
    $membership->onboardingProgress()->firstOrFail()->forceFill([
        'current_step' => 'welcome',
        'recruitment_focus' => null,
        'completed_steps' => [],
        'skipped_steps' => [],
        'completed_at' => null,
        'dismissed_at' => null,
    ])->save();

    return [$organization, $owner];
}

test('public registration is limited to applicants while approved organization onboarding remains separate', function () {
    $this->post(route('register'), [
        'first_name' => 'New', 'last_name' => 'Employer', 'email' => 'employer@example.test',
        'password' => 'Vx9#Lp2qZmT8oWr$', 'password_confirmation' => 'Vx9#Lp2qZmT8oWr$', 'account_type' => 'organization',
    ])->assertRedirect(route('register'))->assertSessionHasErrors('account_type');

    $this->assertDatabaseMissing('users', ['email' => 'employer@example.test']);
    $this->assertDatabaseCount('organization_members', 0);
    $this->assertDatabaseCount('organizations', 0);

    $this->post(route('register'), [
        'first_name' => 'New', 'last_name' => 'Applicant', 'email' => 'applicant@example.test',
        'password' => 'Vx9#Lp2qZmT8oWr$', 'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
    ])->assertRedirect(route('verification.notice', absolute: false));
    $applicant = User::where('email', 'applicant@example.test')->sole();
    expect($applicant->role)->toBe(UserRole::Applicant)->and($applicant->status)->toBe(UserStatus::Pending);
    $this->actingAs($applicant)->get(route('verification.notice'))->assertOk();
});

test('registration presents one applicant Google sign-up button and links organizations to demo requests', function () {
    $page = $this->get(route('register'))->assertOk()->assertSee('Create your applicant account', false);

    expect(substr_count($page->getContent(), 'data-google-signup>'))->toBe(1);
    $page->assertSee('applicant account', false)
        ->assertSee('Request a demo')
        ->assertDontSee('data-applicant-label', false)
        ->assertDontSee('data-organization-label', false);
    $this->get(route('organization-demo.create'))->assertOk()->assertSee('Request a demo');
});

test('public registration cannot create administrator accounts', function () {
    $this->from(route('register'))->post(route('register'), [
        'first_name' => 'Platform', 'last_name' => 'Admin', 'email' => 'admin-signup@example.test',
        'password' => 'Vx9#Lp2qZmT8oWr$', 'password_confirmation' => 'Vx9#Lp2qZmT8oWr$', 'account_type' => 'admin',
    ])->assertRedirect(route('register'))->assertSessionHasErrors('account_type');

    $this->assertDatabaseCount('users', 0);
});

test('new owner onboarding saves progress and organization profile then completes', function () {
    [$organization, $owner] = freshOrganizationOnboardingOwner();
    app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationSetting::query()->updateOrCreate(
        ['organization_id' => $organization->id],
        ['brand_primary_color' => '#123456', 'brand_accent_color' => '#abcdef'],
    ));
    $this->actingAs($owner)->get(route('organisations.dashboard'))->assertRedirect(route('organisations.onboarding.show'));
    $this->get(route('organisations.onboarding.show'))->assertOk()->assertSee('Welcome to your hiring workspace');

    $this->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'recruiter'])
        ->assertRedirect(route('organisations.onboarding.show'));
    $this->get(route('organisations.onboarding.show'))
        ->assertOk()->assertSee('Create your organization profile')
        ->assertDontSee('Primary brand color')->assertDontSee('Accent color');

    $this->from(route('organisations.onboarding.show'))->patch(route('organisations.onboarding.profile'), [
        'version' => 1,
        'name' => 'Marthire Test Company',
        'brand_primary_color' => 'red; background: url(javascript:alert(1))',
    ])->assertRedirect(route('organisations.onboarding.show'))->assertSessionHasErrors('brand_primary_color');
    expect($organization->fresh()->name)->not->toBe('Marthire Test Company');

    $this->patch(route('organisations.onboarding.profile'), [
        'version' => 1,
        'name' => 'Marthire Test Company',
        'industry' => 'Technology',
        'organization_size' => '11-50',
        'about' => 'A company building useful tools.',
    ])->assertRedirect(route('organisations.onboarding.show'))->assertSessionHasNoErrors();

    $settings = OrganizationSetting::query()->where('organization_id', $organization->id)->firstOrFail();
    expect($organization->fresh()->name)->toBe('Marthire Test Company')
        ->and($settings->industry)->toBe('Technology')
        ->and($settings->organization_size)->toBe('11-50')
        ->and($settings->brand_primary_color)->toBe('#123456')
        ->and($settings->brand_accent_color)->toBe('#abcdef');

    $this->get(route('organisations.onboarding.show'))->assertOk()->assertSee('Bring your hiring team in');
    $this->post(route('organisations.onboarding.skip', 'team'))->assertRedirect(route('organisations.onboarding.show'));
    $completion = $this->get(route('organisations.onboarding.show'))->assertOk()->assertSee('Your hiring workspace is ready');
    expect($completion->getContent())
        ->toContain('Create Your First Job')
        ->toContain('Go to Dashboard');

    $this->post(route('organisations.onboarding.finish'), ['next_action' => 'dashboard'])
        ->assertRedirect(route('organisations.dashboard'));

    expect(OrganizationOnboardingProgress::query()->where('organization_member_id', $owner->organizationMemberships()->value('id'))->firstOrFail()->isComplete())->toBeTrue();
    $this->get(route('organisations.dashboard'))->assertOk()->assertDontSee('Finish setting up your workspace');
});

test('organization onboarding persists progress and keeps the dashboard shell inaccessible until completion', function () {
    [, $owner] = freshOrganizationOnboardingOwner();
    $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $owner->id)->firstOrFail();
    $this->actingAs($owner)->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'hr_people'])
        ->assertRedirect(route('organisations.onboarding.show'));
    $progress = $membership->onboardingProgress()->firstOrFail();
    expect($progress->current_step)->toBe('profile')->and($progress->recruitment_focus)->toBe('hr_people');
    $progress->forceFill(['dismissed_at' => now()])->save();

    $this->get(route('organisations.dashboard'))->assertRedirect(route('organisations.onboarding.show'));
    $this->get(route('organisations.jobs.index'))->assertRedirect(route('organisations.onboarding.show'));
    $this->get(route('settings.edit'))->assertRedirect(route('organisations.onboarding.show'));
    $this->get(route('organisations.onboarding.show'))
        ->assertOk()->assertSee('Create your organization profile')->assertSee('hiring-workspace.svg')
        ->assertDontSee('app-sidebar')->assertDontSee('Search jobs');

    expect($progress->fresh()->current_step)->toBe('profile');
});

test('invitation step uses existing team invitations and rejects unauthorized team actions', function () {
    Notification::fake();
    [$organization, $owner] = freshOrganizationOnboardingOwner();
    $this->actingAs($owner)->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'recruitment_lead']);
    $this->post(route('organisations.onboarding.skip', 'profile'))->assertRedirect(route('organisations.onboarding.show'));
    $this->post(route('organisations.onboarding.invitations'), [
        'invitees' => [['email' => 'new-recruiter@example.test', 'role' => 'recruiter']],
    ])->assertRedirect(route('organisations.onboarding.show'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('team_invitations', [
        'organization_id' => $organization->id,
        'email' => 'new-recruiter@example.test',
        'role' => 'recruiter',
        'status' => 'pending',
    ]);
    $this->get(route('organisations.onboarding.show'))->assertOk()->assertSee('Your hiring workspace is ready');
});

test('onboarding completion requires every visible step and can go to the dashboard', function () {
    [, $owner] = freshOrganizationOnboardingOwner();
    $this->actingAs($owner)->post(route('organisations.onboarding.finish'), ['next_action' => 'job'])->assertStatus(409);

    $this->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'other']);
    $this->post(route('organisations.onboarding.skip', 'profile'));
    $this->post(route('organisations.onboarding.skip', 'team'));
    $this->post(route('organisations.onboarding.finish'), ['next_action' => 'dashboard'])
        ->assertRedirect(route('organisations.dashboard'));

    $this->get(route('organisations.onboarding.show'))->assertRedirect(route('organisations.dashboard'));
    expect(OrganizationOnboardingProgress::query()->whereHas('membership', fn ($query) => $query->where('user_id', $owner->id))->firstOrFail()->isComplete())->toBeTrue();
});

test('owner can finish onboarding and continue directly to the authorized job creation workflow', function () {
    [, $owner] = freshOrganizationOnboardingOwner();
    $this->actingAs($owner)->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'other']);
    $this->post(route('organisations.onboarding.skip', 'profile'));
    $this->post(route('organisations.onboarding.skip', 'team'));

    $this->post(route('organisations.onboarding.finish'), ['next_action' => 'job'])
        ->assertRedirect(route('organisations.jobs.create'));

    $this->get(route('organisations.jobs.create'))->assertOk()->assertSee('Post a job');
    expect(OrganizationOnboardingProgress::query()->whereHas('membership', fn ($query) => $query->where('user_id', $owner->id))->firstOrFail()->isComplete())->toBeTrue();
});

test('organization members without job creation permission cannot use the first job action', function () {
    [$organization, $owner] = freshOrganizationOnboardingOwner();
    $membership = addOrganizationMember($organization, OrganizationRole::HiringManager);
    $manager = $membership->user;

    $this->actingAs($manager)->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'hiring_manager'])
        ->assertRedirect(route('organisations.onboarding.show'));

    $this->actingAs($manager)->get(route('organisations.onboarding.show'))
        ->assertOk()->assertDontSee('Create Your First Job')->assertSee('Go to Dashboard');

    $this->post(route('organisations.onboarding.finish'), ['next_action' => 'job'])->assertForbidden();
    expect($membership->onboardingProgress()->firstOrFail()->isComplete())->toBeFalse();

    $this->post(route('organisations.onboarding.finish'), ['next_action' => 'dashboard'])
        ->assertRedirect(route('organisations.dashboard'));
});

test('onboarding company logo upload is previewable and saved through the existing settings flow', function () {
    Storage::fake('public');
    [$organization, $owner] = freshOrganizationOnboardingOwner();
    $this->actingAs($owner)->post(route('organisations.onboarding.welcome'), ['recruitment_focus' => 'other']);
    $page = $this->get(route('organisations.onboarding.show'))->assertOk();
    expect($page->getContent())->toContain('previewImage($event)')->toContain('Remove selected image');

    $logo = UploadedFile::fake()->createWithContent(
        'organization-logo.png',
        file_get_contents(base_path('tests/Fixtures/applications/profile.png')),
    );
    $this->patch(route('organisations.onboarding.profile'), [
        'version' => 1,
        'name' => $organization->name,
        'company_logo' => $logo,
    ])->assertRedirect(route('organisations.onboarding.show'))->assertSessionHasNoErrors();

    $settings = OrganizationSetting::query()->where('organization_id', $organization->id)->firstOrFail();
    expect($settings->company_logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($settings->company_logo_path);
});

test('organization settings exposes the same live logo preview controls', function () {
    [, $owner] = organizationWithOwner();

    $this->actingAs($owner)->get(route('settings.edit'))->assertOk()
        ->assertSee('organizationImageUpload(', false)
        ->assertSee('previewImage($event)', false)
        ->assertSee('imagePreviewFailed($refs.imageInput)', false)
        ->assertSee('Remove selected image');
});

test('public organization career page shows only its published unexpired jobs', function () {
    [$organization, $owner] = organizationWithOwner();
    app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationSetting::query()->create([
        'organization_id' => $organization->id,
        'about' => 'We build technology for people.',
        'industry' => 'Technology',
        'organization_size' => '51-200',
        'brand_primary_color' => '#116644',
        'brand_accent_color' => '#c9f542',
        'public_contact_email' => 'private-internal-check@example.test',
    ]));
    onboardingJob($organization, $owner, 'Open Public Career Role');
    onboardingJob($organization, $owner, 'Draft Private Career Role', JobStatus::Draft);
    onboardingJob($organization, $owner, 'Expired Private Career Role', JobStatus::Open, today()->subDay()->toDateString());
    [$otherOrganization, $otherOwner] = organizationWithOwner();
    onboardingJob($otherOrganization, $otherOwner, 'Different Organization Career Role');

    $this->get(route('organizations.careers.show', $organization->slug))
        ->assertOk()->assertSee('Open Public Career Role')->assertDontSee('Draft Private Career Role')
        ->assertDontSee('Expired Private Career Role')->assertDontSee('Different Organization Career Role')
        ->assertDontSee('private-internal-check@example.test')
        ->assertSee(route('jobs.show', Job::withoutGlobalScope(OrganizationScope::class)->where('title', 'Open Public Career Role')->firstOrFail()), false);
});

test('completed legacy memberships skip onboarding and non-owner personas do not gain permissions', function () {
    [$organization, $owner] = organizationWithOwner();
    $ownerMembership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $owner->id)->firstOrFail();
    $progress = $ownerMembership->onboardingProgress()->firstOrFail();
    $progress->forceFill([
        'current_step' => 'complete',
        'completed_steps' => ['welcome', 'profile', 'team', 'finish'],
        'completed_at' => now(),
    ])->save();
    $this->assertDatabaseHas('organization_onboarding_progress', ['organization_member_id' => $progress->organization_member_id]);
    $this->actingAs($owner)->get(route('organisations.dashboard'))->assertOk();

    $recruiter = User::factory()->organization()->create();
    $membership = app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $recruiter->id,
        'role' => OrganizationRole::Recruiter,
        'status' => 'active',
    ]));
    $steps = app(OrganizationOnboardingService::class)->stepsFor($membership);
    expect($steps)->toBe(['welcome', 'finish']);

    $this->actingAs($recruiter)->get(route('organisations.onboarding.show'))->assertOk()->assertDontSee('Create your organization profile')->assertDontSee('Bring your hiring team in');
    $this->post(route('organisations.onboarding.invitations'), ['invitees' => [['email' => 'unauthorized@example.test', 'role' => 'recruiter']]])->assertForbidden();
});
