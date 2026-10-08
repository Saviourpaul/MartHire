<?php

use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function createPreviewableDocument(): array
{
    Storage::fake('local');
    Storage::fake('public');

    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $job = Job::factory()->for($employer, 'employer')->create();
    $application = ApplicationForm::factory()
        ->for($job, 'job')
        ->for($applicant, 'applicant')
        ->create();
    $document = ApplicationDocument::factory()
        ->for($application, 'applicationForm')
        ->create([
            'file_path' => 'application-documents/1/national-identity-card.pdf',
            'original_name' => 'national-identity-card.pdf',
            'mime_type' => 'application/pdf',
        ]);

    Storage::disk('local')->put($document->file_path, '%PDF-1.4 fake');

    return [$employer, $applicant, $document];
}

it('streams previewable documents inline for authorized users', function () {
    [$employer, $applicant, $document] = createPreviewableDocument();

    $response = $this->actingAs($employer)
        ->get(route('application-documents.preview', $document))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->headers->get('content-disposition'))->toStartWith('inline;')
        ->and($response->headers->get('cache-control'))->toContain('private')
        ->and($response->headers->get('cache-control'))->toContain('no-store');

    $this->actingAs($applicant)
        ->get(route('application-documents.preview', $document))
        ->assertOk();
});

it('prevents unrelated applicants from previewing documents', function () {
    [, , $document] = createPreviewableDocument();

    $this->actingAs(User::factory()->applicant()->create())
        ->get(route('application-documents.preview', $document))
        ->assertForbidden();
});

it('rejects unsupported document preview types while keeping downloads available', function () {
    [$employer, , $document] = createPreviewableDocument();

    $document->update([
        'file_path' => 'application-documents/1/document.txt',
        'original_name' => 'document.txt',
        'mime_type' => 'text/plain',
    ]);

    Storage::disk('local')->put($document->file_path, 'plain text');

    $this->actingAs($employer)
        ->get(route('application-documents.preview', $document))
        ->assertStatus(415);

    $this->actingAs($employer)
        ->get(route('application-documents.download', $document))
        ->assertOk();
});
