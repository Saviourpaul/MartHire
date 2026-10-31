<?php

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CountryDisplayService;
use App\Services\JobCountryService;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

function presentationApplication(array $attributes = []): array
{
    [$organization, $owner] = organizationWithOwner();
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $attributes) {
        $applicant = User::factory()->create(['country_code' => 'NG', 'country' => 'Nigeria']);
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id, 'title' => 'Presentation Engineer',
            'description' => 'A complete engineering role for the candidate presentation workflow.', 'company' => 'Example Co',
            'location' => 'Nigeria', 'employment_type' => 'full_time', 'start_date' => now(), 'due_date' => now()->addMonth(),
        ]);

        return ApplicationForm::create([
            'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
            'reference' => 'PRESENT-'.$job->id, 'status' => CandidatePipelineStage::Submitted, 'submitted_at' => now(),
            'first_name' => 'Example', 'last_name' => 'Candidate', 'email' => 'candidate@example.test', 'phone' => '+2348031234567',
            'country_code' => 'GB', 'country' => 'United Kingdom', ...$attributes,
        ]);
    });

    return [$organization, $owner, $application];
}

test('country presentation resolves ISO codes, legacy aliases and country names consistently', function (?string $code, ?string $name, string $expected) {
    $display = app(CountryDisplayService::class)->present($code, $name);
    expect($display['code'])->toBe($expected)
        ->and($display['flag_url'])->toEndWith('/'.strtolower($expected).'.svg');
})->with([
    ['ng', null, 'NG'], ['NGA', null, 'NG'], ['UK', null, 'GB'],
    [null, 'United States', 'US'], [null, 'Nigeria', 'NG'], ['CI', null, 'CI'],
]);

test('unknown and missing country flags retain escaped names and a visible fallback', function () {
    $countries = Mockery::mock(JobCountryService::class);
    $countries->shouldReceive('all')->andReturn([]);
    $countries->shouldReceive('flagUrl')->with('NG')->andReturn(null);
    app()->instance(CountryDisplayService::class, new CountryDisplayService($countries));
    $html = Blade::render('<x-country-display code="NG" />');
    expect($html)->toContain('Nigeria')->not->toContain('<img');
    $html = Blade::render('<x-country-display :name="$country" />', ['country' => '<script>unsafe()</script>']);
    expect($html)->toContain('&lt;script&gt;unsafe()&lt;/script&gt;')->not->toContain('<script>unsafe');
    $missing = app(CountryDisplayService::class)->present();
    expect($missing['code'])->toBeNull()->and($missing['name'])->toBe('Country not provided');
});

test('legacy snapshot names are not combined with a later profile country code', function () {
    [, $owner, $application] = presentationApplication(['country_code' => null, 'country' => 'United States']);
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertViewHas('review', fn (array $review): bool => $review['profile']['country_data']['code'] === 'US')
        ->assertSee('data-country-code="US"', false)->assertSee('/flags/us.svg', false);
});

test('stored country codes override contradictory display names without modifying the snapshot', function () {
    [, $owner, $application] = presentationApplication(['country_code' => 'GB', 'country' => 'Nigeria']);
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertSee('United Kingdom')->assertSee('/flags/gb.svg', false);
    expect($application->fresh()->country)->toBe('Nigeria');
});

test('candidate previews and activity use standardized types while downloads retain original filenames', function () {
    Storage::fake('local');
    config(['filesystems.application_documents_disk' => 'local']);
    Storage::disk('local')->put('private/sample.pdf', "%PDF-1.7\nSample document");
    [$organization, $owner, $application] = presentationApplication();
    $filename = 'private-original-4493929.pdf';
    $document = app(OrganizationContext::class)->runInOrganization($organization, function () use ($application, $owner, $filename) {
        $document = $application->documents()->create([
            'document_type' => ApplicationDocumentType::Resume, 'document_name' => $filename,
            'file_path' => 'private/sample.pdf', 'original_name' => $filename, 'mime_type' => 'application/pdf', 'size' => 30,
        ]);
        app(ActivityLogger::class)->record($application->organization_id, $application, $owner, 'document_view', ['document_id' => $document->id, 'action' => 'preview']);

        return $document;
    });
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertSee('Résumé / CV')->assertDontSee($filename)
        ->assertViewHas('reviewDocuments', function (array $documents): bool {
            expect($documents[0]['label'])->toBe('Résumé / CV')->and($documents[0])->not->toHaveKeys(['name', 'original_name']);

            return true;
        });
    $this->get($document->downloadUrl())->assertOk()->assertDownload($filename);
});

test('standardized document types do not depend on custom labels or filename extensions', function () {
    $document = new ApplicationDocument([
        'document_type' => ApplicationDocumentType::Education, 'document_name' => 'random-upload.pdf', 'original_name' => 'scan-123.jpg',
    ]);
    expect($document->displayLabel())->toBe('Education certificate');
    $document->setRawAttributes(['document_type' => 'unrecognized_legacy_type']);
    expect($document->displayLabel())->toBe('Document');
});
