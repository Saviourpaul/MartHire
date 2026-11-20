<?php

use App\Support\ApplicationUploadLimits;
use Illuminate\Http\UploadedFile;

it('keeps file payloads below the PHP request body limit with room for multipart data', function () {
    expect(ApplicationUploadLimits::maxTotalFileBytesFor('70M'))->toBe(65 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxTotalFileBytesFor('40M'))->toBe(39 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxTotalFileBytesFor('20M'))->toBe(19 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxTotalFileBytesFor('1M'))->toBeGreaterThan(0);
});

it('caps unlimited PHP request bodies at the form file allowance', function () {
    expect(ApplicationUploadLimits::maxTotalFileBytesFor('0'))->toBe(65 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxTotalFileBytesFor(''))->toBe(65 * 1024 * 1024);
});

it('does not permit file payloads when the PHP request limit is invalid', function () {
    expect(ApplicationUploadLimits::maxTotalFileBytesFor('invalid'))->toBe(0);
});

it('reads the active PHP request and file-count limits', function () {
    expect(ApplicationUploadLimits::requestBodyLimitBytes())->toBeGreaterThan(0)
        ->and(ApplicationUploadLimits::maxFileCount())->toBeGreaterThan(0)
        ->and(ApplicationUploadLimits::maxSingleFileBytes())->toBeGreaterThan(0);
});

it('caps each upload to PHP and the application file policy', function () {
    expect(ApplicationUploadLimits::maxSingleFileBytesFor('2M'))->toBe(2 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxSingleFileBytesFor('40M'))->toBe(5 * 1024 * 1024)
        ->and(ApplicationUploadLimits::maxSingleFileBytesFor('invalid'))->toBe(0);
});

it('rejects server-side file sets that exceed active count and combined size limits', function () {
    $tooManyFiles = collect(range(1, ApplicationUploadLimits::maxFileCount() + 1))
        ->map(fn ($index) => UploadedFile::fake()->create("document-{$index}.pdf", 1))
        ->all();
    $tooLarge = UploadedFile::fake()->create('large.pdf', 66 * 1024);

    expect(ApplicationUploadLimits::violationsForFiles($tooManyFiles))->toHaveKey('file_count')
        ->and(ApplicationUploadLimits::violationsForFiles([$tooLarge]))->toHaveKey('total_bytes');
});
