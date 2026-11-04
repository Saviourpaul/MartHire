<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Storage;

test('only admins can view the user management pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.users.organizations'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.applicants'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.administrators'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.suspended'))->assertOk();

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.users.organizations'))->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.organizations'))->assertForbidden();
});

test('each category only lists users of that category', function () {
    $admin = User::factory()->admin()->create();
    $organization = User::factory()->organization()->create();
    $applicant = User::factory()->create();
    $suspended = User::factory()->create(['status' => UserStatus::Suspended]);

    $this->actingAs($admin)->get(route('admin.users.organizations'))
        ->assertSee($organization->email)->assertDontSee($applicant->email);

    $this->actingAs($admin)->get(route('admin.users.applicants'))
        ->assertSee($applicant->email)->assertDontSee($organization->email);

    $this->actingAs($admin)->get(route('admin.users.suspended'))
        ->assertSee($suspended->email)->assertDontSee($applicant->email);
});

test('search filters the user list', function () {
    $admin = User::factory()->admin()->create();
    $match = User::factory()->create(['first_name' => 'Zoranna']);
    $other = User::factory()->create(['first_name' => 'Regular']);

    $this->actingAs($admin)
        ->get(route('admin.users.applicants', ['search' => 'Zoranna']))
        ->assertSee($match->email)
        ->assertDontSee($other->email);
});

test('admin can create a new user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'first_name' => 'New',
        'last_name' => 'Person',
        'email' => 'new.person@example.com',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
        'role' => UserRole::Applicant->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'new.person@example.com', 'role' => 'applicant']);
});

test('admin can change a user\'s role and status', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $target), [
        'role' => UserRole::Organization->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($target->fresh()->role)->toBe(UserRole::Organization);
});

test('admin can suspend and reactivate a user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.users.suspend', $target))->assertRedirect();
    expect($target->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)->post(route('admin.users.activate', $target))->assertRedirect();
    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

test('admin can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertRedirect();

    expect(User::withTrashed()->find($target->id)?->trashed() ?? true)->toBeTrue();
});

test('the last active admin cannot be demoted or suspended', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
        'role' => UserRole::Applicant->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasErrors();

    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

test('admin can export each user category as csv', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->create(['first_name' => 'Exported', 'last_name' => 'Applicant']);

    $response = $this->actingAs($admin)->get(route('admin.users.applicants.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    $this->assertStringContainsString('Name,Email,Phone,Role,Status,Joined', $csv);
    $this->assertStringContainsString('Exported Applicant', $csv);
    $this->assertStringContainsString($applicant->email, $csv);
});

test('organization and applicant users cannot export the admin user list', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.users.applicants.export'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.applicants.export'))
        ->assertForbidden();
});

test('admin applicant details show profile information once and consolidate private files in one viewer', function () {
    Storage::fake('local');
    config(['filesystems.application_documents_disk' => 'local']);

    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create([
        'phone' => '+234 803 123 4567',
        'date_of_birth' => '1995-06-14',
        'address' => '12 Example Street',
        'city' => 'Lagos',
        'state' => 'Lagos State',
        'country' => 'Nigeria',
        'country_code' => 'NG',
        'nationality' => 'Nigerian',
        'state_of_origin' => 'Oyo State',
        'local_government_area' => 'Ibadan North',
    ]);

    $resumePath = 'candidate-resumes/applicant-resume.pdf';
    $resumeContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
    Storage::disk('local')->put($resumePath, $resumeContent);

    $profile = $applicant->applicantProfile()->create([
        'professional_headline' => 'Senior Laravel engineer',
        'about' => 'Experienced application developer.',
        'resume_path' => $resumePath,
        'resume_original_name' => 'applicant-resume.pdf',
        'resume_mime_type' => 'application/pdf',
        'resume_size' => strlen($resumeContent),
    ]);
    $profile->selfIdentification()->create([
        'gender_identity' => 'female',
        'disability_status' => 'yes',
        'disability_details' => 'Uses a screen reader',
    ]);

    $identityPath = 'user-identification-documents/'.$applicant->id.'/identity.pdf';
    Storage::disk('local')->put($identityPath, $resumeContent);
    $identity = $applicant->identificationDocument()->create([
        'document_type' => ApplicationDocumentType::NationalIdentityCard,
        'file_path' => $identityPath,
        'original_name' => 'identity.pdf',
        'mime_type' => 'application/pdf',
        'size' => strlen($resumeContent),
    ]);

    [$organization, $owner] = organizationWithOwner();
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $applicant, $identity, $identityPath, $resumeContent): void {
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id,
            'title' => 'Profile review role',
            'description' => 'Review applicant profile documents.',
            'company' => 'Profile Review Co',
            'location' => 'Lagos',
            'employment_type' => 'full_time',
            'start_date' => now(),
            'due_date' => now()->addMonth(),
        ]);
        $application = ApplicationForm::create([
            'organization_id' => $organization->id,
            'job_id' => $job->id,
            'user_id' => $applicant->id,
            'reference' => 'ADMIN-DOC-'.$job->id,
            'status' => CandidatePipelineStage::Interview,
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'submitted_at' => now(),
        ]);
        $application->documents()->create([
            'user_identification_document_id' => $identity->id,
            'document_type' => ApplicationDocumentType::NationalIdentityCard,
            'document_name' => 'National Identity Card',
            'file_path' => $identityPath,
            'original_name' => 'identity.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($resumeContent),
        ]);
        $educationPath = 'application-documents/'.$application->id.'/certificate.pdf';
        Storage::disk('local')->put($educationPath, $resumeContent);
        $application->documents()->create([
            'document_type' => ApplicationDocumentType::Education,
            'document_name' => 'Education certificate',
            'file_path' => $educationPath,
            'original_name' => 'certificate.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($resumeContent),
        ]);
    });

    $response = $this->actingAs($admin)->get(route('admin.users.applicants'));

    $response->assertOk()
        ->assertSee('Senior Laravel engineer')
        ->assertSee('Nigerian')
        ->assertSee('Oyo State')
        ->assertSee('Ibadan North')
        ->assertSee('Documents')
        ->assertSee('applicant-resume.pdf')
        ->assertSee('identity.pdf')
        ->assertSee('certificate.pdf')
        ->assertSee('ADMIN-DOC-');

    $html = $response->getContent();
    expect(substr_count($html, '<dt>Phone</dt>'))->toBe(1)
        ->and(substr_count($html, '<dt>Location</dt>'))->toBe(1)
        ->and(substr_count($html, '<dt>Nationality</dt>'))->toBe(1)
        ->and(substr_count($html, '<dt>State of origin</dt>'))->toBe(1)
        ->and(substr_count($html, '<dt>Local government area</dt>'))->toBe(1)
        ->and(substr_count($html, 'identity.pdf'))->toBe(1);
});

test('only active admins can preview applicant profile documents inline', function () {
    Storage::fake('local');
    config(['filesystems.application_documents_disk' => 'local']);

    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create();
    $profile = $applicant->applicantProfile()->create([
        'resume_path' => 'candidate-resumes/private-resume.pdf',
        'resume_original_name' => 'private-resume.pdf',
    ]);
    Storage::disk('local')->put($profile->resume_path, "%PDF-1.4\nprivate resume");

    $response = $this->actingAs($admin)->get(route('admin.users.applicant-resume.preview', $applicant));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');

    $identityPath = 'user-identification-documents/'.$applicant->id.'/identity.pdf';
    Storage::disk('local')->put($identityPath, "%PDF-1.4\nprivate identity");
    $applicant->identificationDocument()->create([
        'document_type' => ApplicationDocumentType::NationalIdentityCard,
        'file_path' => $identityPath,
        'original_name' => 'identity.pdf',
        'mime_type' => 'application/pdf',
    ]);
    $this->actingAs($admin)
        ->get(route('admin.users.identification-document.preview', $applicant))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.users.applicant-resume.preview', $applicant))
        ->assertForbidden();

    $suspendedAdmin = User::factory()->admin()->create();
    $suspendedAdmin->suspend();
    $this->actingAs($suspendedAdmin)
        ->get(route('admin.users.applicant-resume.preview', $applicant))
        ->assertRedirect(route('login'));

    $this->actingAs($admin)
        ->get(route('admin.users.applicant-resume.preview', User::factory()->organization()->create()))
        ->assertNotFound();
});
