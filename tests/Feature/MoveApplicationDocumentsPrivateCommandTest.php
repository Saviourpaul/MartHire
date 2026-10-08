<?php

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;

it('previews public application documents without moving files by default', function () {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/national-identity-card.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, 'National identity document');

    $this->artisan('app:move-application-documents-private')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    Storage::disk('public')->assertExists($document->file_path);
    Storage::disk('local')->assertMissing($document->file_path);
});

it('moves public application documents into private storage when committed', function () {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/international-passport.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, 'Passport document');

    $this->artisan('app:move-application-documents-private', ['--commit' => true])
        ->assertSuccessful();

    Storage::disk('public')->assertExists($document->file_path);
    Storage::disk('local')->assertExists($document->file_path);
});

it('can delete public copies after private storage is verified', function () {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/education.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, 'Education document');

    $this->artisan('app:move-application-documents-private', [
        '--commit' => true,
        '--delete-public' => true,
        '--force' => true,
    ])->assertSuccessful();

    Storage::disk('public')->assertMissing($document->file_path);
    Storage::disk('local')->assertExists($document->file_path);
});

it('reports missing public files without failing the command', function () {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/missing.pdf',
    ]);

    $this->artisan('app:move-application-documents-private', ['--commit' => true])
        ->expectsOutputToContain("Missing public file for document {$document->id}")
        ->assertSuccessful();

    Storage::disk('local')->assertMissing($document->file_path);
});

it('does not delete public copies when existing private files do not match', function () {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/mismatch.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, 'Public document');
    Storage::disk('local')->put($document->file_path, 'Different private document');

    $this->artisan('app:move-application-documents-private', [
        '--commit' => true,
        '--delete-public' => true,
        '--force' => true,
    ])
        ->expectsOutputToContain("Existing private copy verification failed for document {$document->id}")
        ->assertFailed();

    Storage::disk('public')->assertExists($document->file_path);
});
