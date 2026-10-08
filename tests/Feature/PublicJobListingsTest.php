<?php

use App\Models\Job;

function createApprovedPublicJobs(int $count): void
{
    foreach (range(1, $count) as $index) {
        Job::factory()->approved()->create([
            'title' => "Public Listing {$index}",
            'company' => "Listing Company {$index}",
            'category' => 'Technology',
            'location' => 'Nigeria',
            'employment_type' => 'full_time',
            'start_date' => today(),
            'due_date' => today()->addDays(30),
        ]);
    }
}

test('browse jobs displays six cards per page with ajax pagination controls', function () {
    createApprovedPublicJobs(8);

    $initialResponse = $this->get(route('Browse-jobs'));

    $initialResponse->assertOk()
        ->assertSee('data-jobs-results', false)
        ->assertSee('data-jobs-pagination', false);

    expect(substr_count($initialResponse->getContent(), 'data-job-card'))->toBe(6)
        ->and(substr_count($initialResponse->getContent(), 'data-jobs-filter'))->toBe(1);

    $pageTwoResponse = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->getJson(route('Browse-jobs', ['page' => 2]));

    $pageTwoResponse->assertOk()
        ->assertJsonPath('total', 8);

    $fragment = $pageTwoResponse->json('html');

    expect(substr_count($fragment, 'data-job-card'))->toBe(2)
        ->and($fragment)->toContain('data-jobs-pagination')
        ->and($fragment)->not->toContain('<html')
        ->and($fragment)->not->toContain('data-jobs-filter');
});

test('browse jobs renders an empty state for filters with no matches', function () {
    $response = $this->get(route('Browse-jobs', ['search' => 'No Matching Listing']));

    $response->assertOk()
        ->assertSee('No matching jobs')
        ->assertDontSee('data-job-card', false);
});

test('ajax browse jobs returns the empty state from the browse view fragment', function () {
    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->getJson(route('Browse-jobs', ['search' => 'No Matching Listing']));

    $response->assertOk()
        ->assertJsonPath('total', 0);

    $fragment = $response->json('html');

    expect($fragment)->toContain('No matching jobs')
        ->and($fragment)->not->toContain('<html')
        ->and($fragment)->not->toContain('data-jobs-filter');
});
