<?php

use App\Models\CandidateSelfIdentification;
use App\Models\User;
use App\Services\ApplicantProfileChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('applicant profile page shows all profile sections without the dashboard checklist', function () {
    $user = User::factory()->applicant()->create();

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Profile checklist')
        ->assertSee('About you')
        ->assertSee('Save changes')
        ->assertDontSee('Save about you')
        ->assertSee('Resume')
        ->assertSee('Employment')
        ->assertSee('Education')
        ->assertSee('Skills')
        ->assertSee('Online profiles')
        ->assertDontSee('Voluntary self-identification')
        ->assertSee('Gender identity')
        ->assertSee('Male')
        ->assertSee('Female')
        ->assertSee('Disability status')
        ->assertSee('value="yes"', false)
        ->assertSee('value="no"', false)
        ->assertSee("disabilityStatus === 'yes'", false)
        ->assertSee('Work preferences');
});

test('applicant dashboard shows the profile checklist in its right sidebar', function () {
    $user = User::factory()->applicant()->create();

    $this->actingAs($user)->get(route('client.dashboard'))
        ->assertOk()
        ->assertSee('data-live-table="client-dashboard"', false)
        ->assertSee('nw-dashboard-profile-sidebar', false)
        ->assertSee('Profile checklist')
        ->assertSee('0 of 6 complete');
});

test('live dashboard refresh contains the checklist but leaves the openable editor outside the fragment', function () {
    $user = User::factory()->applicant()->create();

    $fragment = $this->actingAs($user)->getJson(route('client.dashboard'), ['X-Live-Tables' => 'client-dashboard'])
        ->assertOk()->json('regions.client-dashboard');

    expect($fragment)->toContain('id="profile-checklist"')
        ->not->toContain('data-applicant-profile-editor-content');
});

test('saving profile details and links persists them and refreshes checklist data', function () {
    $user = User::factory()->applicant()->create();

    $this->actingAs($user)->patch(route('profile.details.update'), [
        'professional_headline' => 'Product designer',
        'about' => 'Designing useful tools for teams.',
        'links' => [
            'linkedin' => 'https://www.linkedin.com/in/example',
            'github' => 'https://github.com/example',
            'website' => '',
        ],
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit').'#online-profiles');

    $profile = $user->applicantProfile()->with('links')->firstOrFail();
    expect($profile->professional_headline)->toBe('Product designer')
        ->and($profile->about)->toBe('Designing useful tools for teams.')
        ->and($profile->links)->toHaveCount(2);

    $checklist = app(ApplicantProfileChecklistService::class)->forUser($user->fresh());
    expect($checklist['items'][4]['complete'])->toBeTrue();
});

test('checklist modal saves asynchronously and returns fresh progress and editor markup', function () {
    $user = User::factory()->applicant()->create();

    $response = $this->actingAs($user)->patchJson(route('profile.details.update'), [
        'professional_headline' => 'Product designer',
        'about' => 'Designing useful tools for teams.',
        'links' => ['linkedin' => '', 'github' => '', 'website' => ''],
    ])->assertOk()->assertJsonPath('message', 'Profile details saved.')
        ->assertJsonPath('progress.total', 6)
        ->assertJsonPath('progress.completed', 0)
        ->assertJsonStructure(['progress', 'checklist_html', 'editor_html']);

    expect($response->json('checklist_html'))->toContain('id="profile-checklist"')
        ->and($response->json('editor_html'))->toContain('Save changes')
        ->and($response->json('editor_html'))->not->toContain('Save contact information')
        ->and($response->json('editor_html'))->not->toContain('Save about you');
});

test('asynchronous preference saves immediately recalculate checklist progress', function () {
    $user = User::factory()->applicant()->create();

    $response = $this->actingAs($user)->patchJson(route('settings.job-preferences.update'), [
        'version' => 0,
        'return_to' => 'settings',
        'job_alert_frequency' => 'weekly',
        'preferred_roles' => ['Developer'],
        'preferred_locations' => ['Lagos'],
        'preferred_job_type' => 'full_time',
        'preferred_workplace' => 'hybrid',
        'profile_visible_to_employers' => '1',
    ])->assertOk()->assertJsonPath('progress.total', 6)
        ->assertJsonPath('progress.completed', 1)
        ->assertJsonPath('progress.percent', 17);

    expect($response->json('checklist_html'))->toContain('Preferences saved')
        ->and($response->json('editor_html'))->toContain('Developer');
});

test('checklist modal uses Laravel JSON validation errors', function () {
    $user = User::factory()->applicant()->create();

    $this->actingAs($user)->postJson(route('profile.employments.store'), [
        'company' => '',
        'title' => '',
        'start_date' => '',
        'is_current' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors(['company', 'title', 'start_date', 'is_current']);
});

test('employment skills and education can be saved and are reflected in the checklist', function () {
    $user = User::factory()->applicant()->create();
    $this->actingAs($user);

    $this->post(route('profile.employments.store'), [
        'company' => 'NearbyWorks',
        'title' => 'Developer',
        'start_date' => '2022-01-01',
        'end_date' => '',
        'is_current' => '1',
        'description' => 'Built product features.',
    ])->assertSessionHasNoErrors();

    $this->patch(route('profile.skills.update'), ['skills' => 'Laravel, Product design'])
        ->assertSessionHasNoErrors();

    $this->post(route('profile.education.store'), [
        'institution' => 'Example University',
        'qualification' => 'BSc',
        'field_of_study' => 'Computer Science',
    ])->assertSessionHasNoErrors();

    $checklist = app(ApplicantProfileChecklistService::class)->forUser($user->fresh());
    expect($checklist['items'][2]['complete'])->toBeTrue()
        ->and($checklist['items'][3]['complete'])->toBeTrue();
});

test('resume uploads are private and can be downloaded only by the owning applicant', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->applicant()->create();
    $resume = UploadedFile::fake()->create('resume.pdf', 12, 'application/pdf');

    $this->actingAs($user)->post(route('profile.resume.store'), ['resume' => $resume])
        ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit').'#resume');

    $path = $user->applicantProfile()->value('resume_path');
    expect($path)->toStartWith('candidate-resumes/');
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);

    $this->get(route('profile.resume.download'))->assertOk();

    $anotherApplicant = User::factory()->applicant()->create();
    $this->actingAs($anotherApplicant)->get(route('profile.resume.download'))->assertNotFound();
});

test('contact identity fields are validated encrypted saved and included in profile completion', function () {
    $user = User::factory()->applicant()->create([
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);
    $user->applicantProfile()->create([
        'professional_headline' => 'Product designer',
        'about' => 'Designing useful tools for teams.',
    ]);

    $payload = [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'date_of_birth' => '1995-05-05',
        'phone' => '08012345678',
        'address' => '10 Example Street',
        'country_code' => 'NG',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'city' => 'Ikeja',
        'gender_identity' => 'female',
        'disability_status' => 'yes',
        'disability_details' => 'Uses a wheelchair',
        'professional_headline' => 'Senior product designer',
        'about' => 'I design accessible products for growing teams.',
    ];

    $this->actingAs($user)->patch(route('profile.update'), [
        ...$payload,
        'disability_details' => '',
    ])->assertSessionHasErrors('disability_details');

    $this->patch(route('profile.update'), [...$payload, 'gender_identity' => 'other'])
        ->assertSessionHasErrors('gender_identity');

    $this->patchJson(route('profile.update'), $payload)
        ->assertOk()
        ->assertJsonPath('progress.total', 6)
        ->assertJsonPath('progress.completed', 1);

    $identification = CandidateSelfIdentification::query()->firstOrFail();
    $rawGender = DB::table('candidate_self_identifications')->where('id', $identification->id)->value('gender_identity');
    $rawDetails = DB::table('candidate_self_identifications')->where('id', $identification->id)->value('disability_details');
    expect($identification->gender_identity)->toBe('female')
        ->and($identification->disability_status)->toBe('yes')
        ->and($identification->disability_details)->toBe('Uses a wheelchair')
        ->and($rawGender)->not->toBe('female')
        ->and($rawDetails)->not->toBe('Uses a wheelchair');

    expect($user->applicantProfile()->firstOrFail()->professional_headline)->toBe('Senior product designer')
        ->and($user->applicantProfile()->firstOrFail()->about)->toBe('I design accessible products for growing teams.');

    $checklist = app(ApplicantProfileChecklistService::class)->forUser($user->fresh());
    expect($checklist['items'][1]['complete'])->toBeTrue()
        ->and($checklist['total'])->toBe(6);

    $this->patch(route('profile.update'), [...$payload, 'disability_status' => 'no'])
        ->assertSessionHasNoErrors();

    expect($identification->fresh()->disability_details)->toBeNull();
});

test('work preferences save from the profile page and update profile completion', function () {
    $user = User::factory()->applicant()->create();

    $this->actingAs($user)->patch(route('settings.job-preferences.update'), [
        'version' => 0,
        'return_to' => 'profile',
        'job_alert_frequency' => 'weekly',
        'preferred_roles' => 'Developer, Designer',
        'preferred_locations' => 'Lagos',
        'preferred_job_type' => 'full_time',
        'preferred_workplace' => 'hybrid',
        'profile_visible_to_employers' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit').'#work-preferences');

    $checklist = app(ApplicantProfileChecklistService::class)->forUser($user->fresh());
    expect($checklist['items'][5]['complete'])->toBeTrue();
});

test('applicants cannot edit another applicants employment record', function () {
    $owner = User::factory()->applicant()->create();
    $other = User::factory()->applicant()->create();
    $employment = $owner->applicantProfile()->create()->employments()->create([
        'company' => 'Private Company',
        'title' => 'Private role',
        'start_date' => '2021-01-01',
        'is_current' => true,
    ]);

    $this->actingAs($other)->patch(route('profile.employments.update', $employment), [
        'company' => 'Changed',
        'title' => 'Changed',
        'start_date' => '2021-01-01',
        'is_current' => true,
    ])->assertNotFound();

    expect($employment->fresh()->company)->toBe('Private Company');
});
