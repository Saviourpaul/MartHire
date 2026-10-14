<?php

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function jobDescriptionDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    $previousErrorHandling = libxml_use_internal_errors(true);

    try {
        $document->loadHTML($html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);
    }

    return new DOMXPath($document);
}

it('retains description input and accessible feedback after validation fails', function () {
    $description = "First paragraph.\n\n</textarea><script>alert('unsafe')</script>";
    $errors = (new ViewErrorBag)->put('default', new MessageBag([
        'description' => 'The description field is required.',
    ]));

    $response = $this->actingAs(User::factory()->employer()->create())
        ->withSession(['_old_input' => ['description' => $description], 'errors' => $errors])
        ->get(route('jobs'))
        ->assertOk();
    $document = jobDescriptionDocument($response->getContent());
    $field = $document->query('//textarea[@name="description"]')->item(0);
    $error = $document->query('//*[@id="create_job_description_error"]')->item(0);

    expect($field->textContent)->toBe($description)
        ->and($field->hasAttribute('required'))->toBeTrue()
        ->and($field->getAttribute('aria-invalid'))->toBe('true')
        ->and($field->getAttribute('aria-describedby'))->toBe('create_job_description_hint create_job_description_error')
        ->and(trim($error->textContent))->toBe('The description field is required.')
        ->and($error->getAttribute('class'))->not->toContain('hidden')
        ->and($document->query('.//script', $field->parentNode)->length)->toBe(0);
});

it('retains the saved description in the shared edit field', function () {
    $description = "Maintain our APIs.\n\nSupport the engineering team.";
    $document = jobDescriptionDocument(view('employer.jobs._form', [
        'job' => Job::factory()->make(['description' => $description]),
        'prefix' => 'edit_job_1',
        'errors' => new ViewErrorBag,
    ])->render());
    $field = $document->query('//textarea[@name="description"]')->item(0);

    expect($field->textContent)->toBe($description)
        ->and($field->getAttribute('id'))->toBe('edit_job_1_description')
        ->and($field->getAttribute('aria-invalid'))->toBe('false');
});

it('preserves description validation and multiline values in ajax job creation', function () {
    $employer = User::factory()->employer()->create();
    $data = [
        'title' => 'Backend Developer',
        'company' => 'Marthire',
        'location' => 'Lagos, Nigeria',
        'employment_type' => 'full_time',
        'start_date' => today()->toDateString(),
        'due_date' => today()->addDays(30)->toDateString(),
        'description' => '',
    ];

    $this->actingAs($employer)->postJson(route('jobs.store'), $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');

    expect(Job::count())->toBe(0);

    $description = "Build reliable services.\n\nCollaborate with the product team.";
    $this->postJson(route('jobs.store'), [...$data, 'description' => $description])
        ->assertSuccessful();

    expect(Job::query()->firstOrFail())
        ->description->toBe($description)
        ->status->toBe(JobStatus::Open);
});

it('rejects empty or oversized rich descriptions when creating and updating jobs', function (mixed $description) {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->for($employer, 'employer')->create();
    $data = [
        'title' => 'Backend Developer',
        'company' => 'Marthire',
        'location' => 'Lagos',
        'employment_type' => 'full_time',
        'start_date' => today()->toDateString(),
        'due_date' => today()->addDays(30)->toDateString(),
        'description' => $description,
    ];

    $this->actingAs($employer)->postJson(route('jobs.store'), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('description');
    $this->putJson(route('jobs.update', $job), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('description');

    expect(Job::count())->toBe(1)
        ->and($job->fresh()->description)->toBe($job->description);
})->with([
    'empty paragraph' => '<p><br></p>',
    'invisible text' => '<p>&nbsp; &#8203;</p>',
    'script only' => '<script>alert(1)</script>',
    'image only' => '<img src="https://example.com/team.jpg">',
    'oversized content' => str_repeat('a', 100001),
    'invalid type' => [123],
]);

it('shows independent rich description fields in create and edit dialogs', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->for($employer, 'employer')->create(['description' => '<h2>Saved role</h2><p>Build APIs.</p>']);
    $response = $this->actingAs($employer)->get(route('jobs'))->assertOk();
    $document = jobDescriptionDocument($response->getContent());

    expect($document->query('//*[@data-job-description-editor]')->length)->toBe(2)
        ->and($document->query('//script[contains(@src, "assets/js/flowbite.js")]')->length)->toBe(1)
        ->and($document->query('//textarea[@id="edit_job_'.$job->id.'_description"]')->item(0)->textContent)->toBe($job->description)
        ->and($document->query('//*[@role="dialog" and @aria-modal="true"]')->length)->toBe(2);

    $this->get(route('jobs.show', $job))->assertOk()
        ->assertSee('<h2>Saved role</h2>', false)
        ->assertSee('data-job-description-editor', false);
});

it('preserves legacy plain text paragraphs on public job details', function () {
    $job = Job::factory()->open()->create([
        'description' => "Build reliable services.\n\nHelp candidates succeed & grow.",
        'due_date' => today()->addDays(30),
    ]);

    $this->get(route('job-details', $job))->assertOk()
        ->assertSee('Build reliable services.<br />', false)
        ->assertSee('Help candidates succeed &amp; grow.', false);
});

it('displays sanitized rich job descriptions with Flowbite typography', function (bool $signedIn) {
    $description = '<h2>Responsibilities</h2><p style="text-align: center">Support <strong>Laravel</strong> APIs.</p><ul><li>Own APIs</li></ul><img src="https://example.com/team.png" alt="Our team" onerror="alert(1)"><a href="javascript:alert(1)">Unsafe link</a><script>alert(2)</script>';
    $job = Job::factory()->open()->create([
        'description' => $description,
        'due_date' => today()->addDays(30),
    ]);

    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    $response = $this->get(route('job-details', $job))->assertOk();
    $document = jobDescriptionDocument($response->getContent());
    $descriptionElement = $document->query('//*[@data-job-description]')->item(0);

    expect($descriptionElement)->not->toBeNull()
        ->and($descriptionElement->textContent)->toContain('Responsibilities', 'Laravel', 'Own APIs')
        ->and($descriptionElement->getAttribute('class'))->toContain('format-gray')
        ->and($document->query('.//script | .//*[@onerror] | .//a[starts-with(@href, "javascript:")]', $descriptionElement)->length)->toBe(0)
        ->and($document->query('.//h2 | .//strong | .//ul | .//img[@alt="Our team"]', $descriptionElement)->length)->toBe(4)
        ->and($document->query('.//p', $descriptionElement)->item(0)->getAttribute('style'))->toBe('text-align: center');
})->with([
    'public visitor' => false,
    'signed-in candidate' => true,
]);

it('allows an authenticated employer to manage jobs', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('jobs'))
        ->assertOk()
        ->assertSee('Jobs');

    $description = '<p>Build and maintain <strong>Laravel</strong> services.</p><ul><li>Own APIs</li></ul>';

    $createData = [
        'title' => 'Backend Developer',
        'description' => $description,
        'company' => 'Nexhire Labs',
        'category' => 'Technology',
        'logo' => 'admin/assets/img/company/img-10.png',
        'location' => 'Lagos',
        'employment_type' => 'full_time',
        'start_date' => today()->toDateString(),
        'due_date' => today()->addDays(30)->toDateString(),
        'status' => JobStatus::Open->value,
    ];

    $this->actingAs($employer)
        ->post(route('jobs.store'), $createData)
        ->assertRedirect(route('jobs'));

    $job = Job::query()->firstOrFail();

    expect($job->employer_id)->toBe($employer->id);
    expect($job->description)->toBe($description);
    expect($job->status)->toBe(JobStatus::Open);
    $this->assertDatabaseHas('job_posts', [
        'id' => $job->id,
        'title' => 'Backend Developer',
        'company' => 'Nexhire Labs',
        'category' => 'Technology',
        'status' => JobStatus::Open->value,
    ]);

    $this->actingAs($employer)
        ->get(route('jobs'))
        ->assertOk()
        ->assertSee('Backend Developer')
        ->assertSee('Nexhire Labs');

    $job->forceFill(['status' => JobStatus::Open])->save();

    $this->get(route('job-details', $job->fresh()))
        ->assertOk()
        ->assertSee('Build and maintain')
        ->assertSee('Own APIs')
        ->assertSee('<strong>Laravel</strong>', false);

    $this->actingAs($employer)
        ->put(route('jobs.update', $job), [
            ...$createData,
            'title' => 'Senior Backend Developer',
            'status' => JobStatus::Open->value,
        ])
        ->assertRedirect(route('jobs'));

    expect($job->fresh())
        ->title->toBe('Senior Backend Developer')
        ->status->toBe(JobStatus::Open);

    $this->actingAs($employer)
        ->delete(route('jobs.destroy', $job))
        ->assertRedirect(route('jobs'));

    $this->assertDatabaseMissing('job_posts', [
        'id' => $job->id,
    ]);
});

it('prevents employers from changing another employers jobs', function () {
    $owner = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $job = $owner->jobs()->create([
        'organization_id' => $owner->organizationMemberships()->withoutGlobalScopes()->firstOrFail()->organization_id,
        'title' => 'Frontend Developer',
        'description' => 'Build polished interfaces.',
        'company' => 'Nexhire Labs',
        'category' => 'Technology',
        'start_date' => '2026-06-01',
        'due_date' => '2026-06-30',
        'status' => JobStatus::Open,
    ]);

    $this->actingAs($otherEmployer)
        ->put(route('jobs.update', $job), [
            'title' => 'Changed Title',
            'description' => 'Changed description.',
            'company' => 'Changed Company',
            'category' => 'Technology',
            'start_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'status' => JobStatus::Suspended->value,
        ])
        ->assertNotFound();

    expect(Job::withoutGlobalScopes()->findOrFail($job->id)->title)->toBe('Frontend Developer');
});

it('rejects svg job logo uploads', function () {
    Storage::fake('public');

    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->post(route('jobs.store'), [
            'title' => 'Backend Developer',
            'description' => 'Build secure services.',
            'company' => 'Nexhire Labs',
            'category' => 'Technology',
            'logo' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
            'start_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'status' => JobStatus::Open->value,
        ])
        ->assertSessionHasErrors('logo');

    expect(Job::count())->toBe(0);
});

it('prevents applicants from managing jobs', function () {
    $applicant = User::factory()->create();

    $this->actingAs($applicant)
        ->get(route('jobs'))
        ->assertForbidden();
});
