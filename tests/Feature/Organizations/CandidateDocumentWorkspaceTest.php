<?php

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\JobPostMember;
use App\Models\User;
use App\Services\ApplicationDocumentFileService;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Storage;

/** @return array{owner: User, applicant: User, application: ApplicationForm, document: ApplicationDocument} */
function candidateDocumentWorkspace(array $attributes = [], string $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"): array
{
    Storage::fake('local');
    config(['filesystems.application_documents_disk' => 'local']);
    [$organization, $owner] = organizationWithOwner();
    $applicant = User::factory()->applicant()->create();

    return app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $applicant, $attributes, $content): array {
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id, 'title' => 'Document review role',
            'description' => 'Review submitted candidate documents.', 'company' => 'Review Co',
            'location' => 'Nigeria', 'employment_type' => 'full_time',
            'start_date' => now(), 'due_date' => now()->addMonth(),
        ]);
        $application = ApplicationForm::create([
            'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
            'reference' => 'DOC-'.$job->id, 'status' => CandidatePipelineStage::Interview,
            'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name,
            'email' => $applicant->email, 'phone' => '+2348031234567', 'submitted_at' => now(),
        ]);
        $document = $application->documents()->create(array_replace([
            'document_type' => ApplicationDocumentType::Resume, 'document_name' => 'Curriculum vitae',
            'original_name' => 'candidate-cv.pdf', 'file_path' => 'application-documents/'.$application->id.'/resume.pdf',
            'mime_type' => 'application/pdf', 'size' => strlen($content),
        ], $attributes));
        Storage::disk('local')->put($document->file_path, $content);

        return compact('owner', 'applicant', 'application', 'document');
    });
}

test('candidate PDF previews remain private same-origin streams and record an access event', function () {
    extract(candidateDocumentWorkspace());
    $response = $this->actingAs($owner)->get(route('application-documents.preview', $document));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    expect($response->streamedContent())->toStartWith('%PDF-1.4');
    $event = ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->sole();
    expect($event->type)->toBe('document_view')->and($event->actor_id)->toBe($owner->id)
        ->and($event->payload['document_id'])->toBe($document->id)->and($event->payload['action'])->toBe('preview');
});

test('candidate JPEG and PNG files can be previewed inline', function (string $extension, string $content, string $mime) {
    extract(candidateDocumentWorkspace(['original_name' => 'certificate.'.$extension, 'mime_type' => $mime], base64_decode($content)));
    $response = $this->actingAs($owner)->get(route('application-documents.preview', $document));
    $response->assertOk()->assertHeader('Content-Type', $mime);
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
})->with([
    ['png', 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4ioAAAAASUVORK5CYII=', 'image/png'],
    ['jpg', '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJgA/9k=', 'image/jpeg'],
]);

test('download retains attachment disposition and writes a separate audit action', function () {
    extract(candidateDocumentWorkspace(['original_name' => 'candidate-cv.docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'Office file content'));
    $response = $this->actingAs($owner)->get(route('application-documents.download', $document));

    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment;')->toContain('candidate-cv.docx');
    expect($response->streamedContent())->toBe('Office file content');
    $event = ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->sole();
    expect($event->payload['action'])->toBe('download');
});

test('unsafe or unsupported stored content never previews even with spoofed PDF metadata', function (string $content) {
    extract(candidateDocumentWorkspace(['mime_type' => 'application/pdf', 'original_name' => 'safe-looking.pdf'], $content));
    $this->actingAs($owner)->get(route('application-documents.preview', $document))->assertStatus(415);
    expect(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->exists())->toBeFalse();
})->with([
    '<!DOCTYPE html><html><script>alert(1)</script></html>',
    '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
    'Unsupported office document content',
]);

test('missing private files return not found without recording a successful access', function () {
    extract(candidateDocumentWorkspace());
    Storage::disk('local')->delete($document->file_path);
    $this->actingAs($owner)->get(route('application-documents.preview', $document))->assertNotFound();
    $this->actingAs($owner)->get(route('application-documents.download', $document))->assertNotFound();
    expect(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->exists())->toBeFalse();
    expect(app(ApplicationDocumentFileService::class)->metadataFor($document))->toBe([
        'available' => false, 'previewable' => false, 'mime_type' => null,
    ]);
});

test('workspace metadata and stream headers use the same detected MIME for legacy records', function () {
    extract(candidateDocumentWorkspace(['mime_type' => 'text/plain']));
    $metadata = app(ApplicationDocumentFileService::class)->metadataFor($document);
    expect($metadata)->toBe(['available' => true, 'previewable' => true, 'mime_type' => 'application/pdf']);
    $this->actingAs($owner)->get(route('application-documents.preview', $document))
        ->assertOk()->assertHeader('Content-Type', $metadata['mime_type']);
});

test('document streams enforce tenant boundaries and applicant ownership', function () {
    extract(candidateDocumentWorkspace());
    [, $otherOwner] = organizationWithOwner();
    $otherApplicant = User::factory()->applicant()->create();

    foreach (['application-documents.preview', 'application-documents.download'] as $name) {
        $this->actingAs($otherOwner)->get(route($name, $document))->assertForbidden();
        $this->actingAs($otherApplicant)->get(route($name, $document))->assertForbidden();
        $this->actingAs($applicant)->get(route($name, $document))->assertOk();
    }
});

test('an assigned interviewer can review a candidate without gaining access to documents', function () {
    extract(candidateDocumentWorkspace());
    $organization = $application->organization;
    $interviewer = addOrganizationMember($organization, OrganizationRole::Interviewer)->user;
    app(OrganizationContext::class)->runInOrganization($organization, fn () => JobPostMember::create([
        'organization_id' => $organization->id, 'job_id' => $application->job_id,
        'user_id' => $interviewer->id,
    ]));
    $this->actingAs($interviewer)->get(route('organisations.candidates.show', $application))->assertOk();
    $this->actingAs($interviewer)->get(route('application-documents.preview', $document))->assertForbidden();
    $this->actingAs($interviewer)->get(route('application-documents.download', $document))->assertForbidden();
});

test('document routes require an authenticated active account', function () {
    extract(candidateDocumentWorkspace());
    $this->get(route('application-documents.preview', $document))->assertRedirect(route('login'));
    $this->get(route('application-documents.download', $document))->assertRedirect(route('login'));
    $owner->suspend();
    $this->actingAs($owner)->get(route('application-documents.preview', $document))->assertRedirect(route('login'));
});
