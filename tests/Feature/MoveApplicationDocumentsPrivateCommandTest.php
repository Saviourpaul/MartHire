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

it('does not delete public copies when existing private files do not match', function (string $publicContents, string $privateContents): void {
    Storage::fake('public');
    Storage::fake('local');

    $document = ApplicationDocument::factory()->create([
        'file_path' => 'application-documents/1/mismatch.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, $publicContents);
    Storage::disk('local')->put($document->file_path, $privateContents);

    $this->artisan('app:move-application-documents-private', [
        '--commit' => true,
        '--delete-public' => true,
        '--force' => true,
    ])
        ->expectsOutputToContain("Existing private copy verification failed for document {$document->id}")
        ->assertFailed();

    Storage::disk('public')->assertExists($document->file_path);
    expect(Storage::disk('public')->get($document->file_path))->toBe($publicContents)
        ->and(Storage::disk('local')->get($document->file_path))->toBe($privateContents);
})->with([
    'different sizes' => ['Public document', 'Different private document'],
    'same size corrupted content' => ['Public document', 'Broken document'],
]);

it('reports referenced public copies even when a private copy already exists', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $document = ApplicationDocument::factory()->create(['file_path' => 'application-documents/1/copied.pdf']);
    Storage::disk('public')->put($document->file_path, 'Identical contents');
    Storage::disk('local')->put($document->file_path, 'Identical contents');

    $this->artisan('app:move-application-documents-private')
        ->expectsTable(['Metric', 'Count'], [
            ['scanned', 1],
            ['public copies', 1],
            ['would move', 0],
            ['moved', 0],
            ['already private', 1],
            ['public deleted', 0],
            ['missing public', 0],
            ['blank path', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    Storage::disk('public')->assertExists($document->file_path);
    Storage::disk('local')->assertExists($document->file_path);
});

it('rejects file paths that are not safely relative to the storage roots', function (string $path): void {
    Storage::fake('public');
    Storage::fake('local');
    $document = ApplicationDocument::factory()->create(['file_path' => $path]);

    $this->artisan('app:move-application-documents-private', [
        '--commit' => true, '--delete-public' => true, '--force' => true,
    ])
        ->expectsOutputToContain("Unsafe file path for document {$document->id}.")
        ->assertFailed();
})->with([
    'traversal' => '../outside.pdf',
    'absolute unix path' => '/outside.pdf',
    'absolute windows path' => 'C:\\outside.pdf',
    'windows traversal' => 'application-documents\\..\\..\\outside.pdf',
    'windows trailing space traversal' => 'application-documents/.. /.. /outside.pdf',
    'stream scheme' => 'file://outside.pdf',
    'control byte' => "application-documents/invalid\0.pdf",
]);

it('migrates unreferenced sensitive files only when explicitly included', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $identityPath = 'user-identification-documents/9/legacy-identity.pdf';
    $applicationPath = 'application-documents/9/legacy-certificate.pdf';
    Storage::disk('public')->put($identityPath, 'Unreferenced identity contents');
    Storage::disk('public')->put($applicationPath, 'Unreferenced certificate contents');
    Storage::disk('public')->put('job-logos/keep.png', 'Company logo');

    $this->artisan('app:move-application-documents-private', [
        '--commit' => true, '--delete-public' => true, '--force' => true,
    ])->assertSuccessful();
    Storage::disk('public')->assertExists([$identityPath, $applicationPath]);
    Storage::disk('local')->assertMissing([$identityPath, $applicationPath]);

    $this->artisan('app:move-application-documents-private', ['--include-unreferenced' => true])
        ->assertSuccessful();
    Storage::disk('public')->assertExists([$identityPath, $applicationPath]);
    Storage::disk('local')->assertMissing([$identityPath, $applicationPath]);

    $this->artisan('app:move-application-documents-private', [
        '--include-unreferenced' => true, '--commit' => true, '--delete-public' => true, '--force' => true,
    ])->assertSuccessful();
    Storage::disk('public')->assertMissing([$identityPath, $applicationPath]);
    expect(Storage::disk('local')->get($identityPath))->toBe('Unreferenced identity contents')
        ->and(Storage::disk('local')->get($applicationPath))->toBe('Unreferenced certificate contents');
    Storage::disk('public')->assertExists('job-logos/keep.png');
    Storage::disk('local')->assertMissing('job-logos/keep.png');
});

it('deduplicates referenced file paths while scanning the public document folders', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $path = 'application-documents/1/shared.pdf';
    ApplicationDocument::factory()->count(2)->create(['file_path' => $path]);
    Storage::disk('public')->put($path, 'Shared document contents');

    $this->artisan('app:move-application-documents-private', ['--include-unreferenced' => true])
        ->expectsTable(['Metric', 'Count'], [
            ['scanned', 1], ['public copies', 1], ['would move', 1], ['moved', 0],
            ['already private', 0], ['public deleted', 0], ['missing public', 0],
            ['blank path', 0], ['failed', 0],
        ])
        ->assertSuccessful();
});
