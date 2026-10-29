<?php

use App\Enums\JobStatus;

test('DUMP the rendered create-form description region', function () {
    [$organization, $owner] = organizationWithOwner();

    $html = $this->actingAs($owner)->get(route('organisations.jobs.create'))->getContent();

    $start = strpos($html, 'Job description');
    $slice = substr($html, $start - 40, 1400);
    file_put_contents(base_path('storage/jobform-dump.html'), $slice);

    expect(true)->toBeTrue();
});
