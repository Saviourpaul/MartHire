<?php

use App\Enums\JobStatus;
use Illuminate\Support\Facades\Blade;

test('a draft job status renders its badge without error', function () {
    $html = Blade::render('<x-status-badge :status="$s" />', ['s' => JobStatus::Draft]);

    expect($html)->toContain('Draft')->toContain('nw-status--draft');
});

test('draft is excluded from the admin-moderatable statuses', function () {
    $moderatable = JobStatus::moderatableCases();

    expect($moderatable)->not->toContain(JobStatus::Draft)
        ->and($moderatable)->toContain(JobStatus::Open)
        ->and($moderatable)->toContain(JobStatus::Suspended)
        ->and($moderatable)->toContain(JobStatus::Closed);
});
