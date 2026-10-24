<?php

use App\Models\JobPosting;

it('lists open postings and excludes closed ones', function () {
    $open = JobPosting::factory()->create(['title' => 'Open Role Alpha']);
    JobPosting::factory()->closed()->create(['title' => 'Closed Role Beta']);

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertSee('Open Role Alpha')
        ->assertDontSee('Closed Role Beta');
});

it('shows a job detail page with JSON-LD and a redirect apply link', function () {
    $job = JobPosting::factory()->create([
        'title' => 'Staff Engineer',
        'application_url' => 'https://apply.workable.com/acme/j/ABCD1234/',
        'content_html' => '<p>Build great things.</p>',
    ]);

    $response = $this->get(route('jobs.show', $job->shortcode))->assertOk();

    $response->assertSee('Staff Engineer');
    $response->assertSee('application/ld+json', false);
    $response->assertSee('JobPosting', false);
    // Apply is redirect-only in W2: links out to the hosted Workable URL.
    $response->assertSee('https://apply.workable.com/acme/j/ABCD1234/', false);
});

it('sanitises malicious employer HTML before rendering', function () {
    $job = JobPosting::factory()->create([
        'content_html' => '<p>Safe <strong>intro</strong>.</p>'
            .'<script>alert(1)</script>'
            .'<p onclick="alert(1)">click me</p>'
            .'<a href="javascript:alert(1)">bad link</a>'
            .'<img src=x onerror="alert(1)">',
    ]);

    $response = $this->get(route('jobs.show', $job->shortcode))->assertOk();

    // Safe content survives.
    $response->assertSee('intro', false);
    $response->assertSee('click me', false);
    $response->assertSee('bad link', false);
    // XSS vectors are neutralised.
    $response->assertDontSee('alert(1)', false);
    $response->assertDontSee('onerror', false);
    $response->assertDontSee('onclick', false);
    $response->assertDontSee('javascript:', false);
});

it('shows a closed notice instead of an apply button for a closed posting', function () {
    $job = JobPosting::factory()->closed()->create(['title' => 'Retired Role']);

    $this->get(route('jobs.show', $job->shortcode))
        ->assertOk()
        ->assertSee('no longer accepting applications');
});

it('returns 404 for an unknown shortcode', function () {
    $this->get(route('jobs.show', 'NOSUCHCODE'))->assertNotFound();
});
