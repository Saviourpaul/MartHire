<?php

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('shows real open postings on the homepage without sample job links', function () {
    $job = JobPosting::factory()->create(['title' => 'Homepage Backend Engineer']);
    JobPosting::factory()->closed()->create(['title' => 'Closed Homepage Role']);

    $this->get('/')
        ->assertOk()
        ->assertSee($job->title)
        ->assertSee(route('workable-jobs.show', $job->shortcode), false)
        ->assertDontSee('Closed Homepage Role')
        ->assertDontSee('/workable-jobs/sample-', false);
});

it('redirects an applicant from the generic dashboard to their own dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('client.dashboard'));
});

it('lists open postings and excludes closed ones', function () {
    $open = JobPosting::factory()->create(['title' => 'Open Role Alpha']);
    JobPosting::factory()->closed()->create(['title' => 'Closed Role Beta']);

    $this->get(route('workable-jobs.index'))
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

    $response = $this->get(route('workable-jobs.show', $job->shortcode))->assertOk();

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

    $response = $this->get(route('workable-jobs.show', $job->shortcode))->assertOk();

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

    $this->get(route('workable-jobs.show', $job->shortcode))
        ->assertOk()
        ->assertSee('no longer accepting applications');
});

it('returns 404 for an unknown shortcode', function () {
    $this->get(route('workable-jobs.show', 'NOSUCHCODE'))->assertNotFound();
});

it('redirects an authenticated application request to the hosted employer URL', function () {
    $job = JobPosting::factory()->create([
        'application_url' => 'https://apply.workable.com/acme/j/HOSTED123/',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('workable-jobs.apply', $job->shortcode))
        ->assertRedirect($job->application_url);
});

it('requires login before visiting the application route', function () {
    $job = JobPosting::factory()->create();

    $this->get(route('workable-jobs.apply', $job->shortcode))
        ->assertRedirect(route('login'));
});

it('does not allow an application for a closed posting', function () {
    $job = JobPosting::factory()->closed()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('workable-jobs.apply', $job->shortcode))
        ->assertNotFound();
});

it('previews the real job application without sending HTTP requests when no hosted URL exists', function () {
    Http::fake();
    $job = JobPosting::factory()->create([
        'title' => 'Preview Backend Engineer',
        'application_url' => null,
        'shortlink' => null,
        'url' => null,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('workable-jobs.apply', $job->shortcode))
        ->assertOk()
        ->assertViewIs('Apply')
        ->assertViewHas('job', fn (array $presented) => $presented['id'] === $job->shortcode)
        ->assertViewHas('preview', true)
        ->assertViewHas('form', function (array $form): bool {
            expect(array_column($form['fields'], 'key'))->toBe(['firstname', 'lastname', 'email', 'phone', 'resume', 'cover_letter'])
                ->and($form['questions'])->toBeEmpty();

            return true;
        })
        ->assertSee($job->title)
        ->assertSee('This is a preview.')
        ->assertDontSee('/workable-jobs/sample-', false);

    Http::assertNothingSent();
    $this->assertDatabaseCount('job_applications', 0);
});
