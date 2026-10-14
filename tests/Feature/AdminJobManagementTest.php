<?php

use App\Enums\JobStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationFormService;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function adminJobListingsDocument(string $html): DOMXPath
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

it('lets admins browse search filter and sort all employer job postings', function () {
    $admin = User::factory()->admin()->create();
    $alphaEmployer = User::factory()->employer()->create([
        'first_name' => 'Ada',
        'last_name' => 'Recruiter',
        'email' => 'ada@example.com',
    ]);
    $betaEmployer = User::factory()->employer()->create([
        'first_name' => 'Bola',
        'last_name' => 'Hiring',
        'email' => 'bola@example.com',
    ]);

    $approved = Job::factory()->open()->for($alphaEmployer, 'employer')->create([
        'title' => 'Civil Engineer',
        'company' => 'Acme Works',
        'category' => 'Engineering',
        'created_at' => now()->subDays(3),
    ]);

    $pending = Job::factory()->closed()->for($betaEmployer, 'employer')->create([
        'title' => 'Finance Analyst',
        'company' => 'Beta Finance',
        'category' => 'Finance',
        'created_at' => now()->subDay(),
    ]);

    $rejected = Job::factory()->suspended()->for($alphaEmployer, 'employer')->create([
        'title' => 'Clinic Administrator',
        'company' => 'Care Group',
        'category' => 'Healthcare',
        'created_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.jobs.index'))
        ->assertOk()
        ->assertSee('All Jobs')
        ->assertSee('Suspended Jobs')
        ->assertSee('Job Moderation')
        ->assertSee('Job status')
        ->assertSee($approved->title)
        ->assertSee($pending->title)
        ->assertSee($rejected->title)
        ->assertSee($alphaEmployer->email)
        ->assertSee($betaEmployer->email);

    $this->actingAs($admin)
        ->get(route('closed-jobs'))
        ->assertOk()
        ->assertSee($pending->title)
        ->assertDontSee($approved->title)
        ->assertDontSee($rejected->title);

    $this->actingAs($admin)
        ->get(route('admin.jobs.index', ['search' => 'Bola']))
        ->assertOk()
        ->assertSee($pending->title)
        ->assertDontSee($approved->title);

    $this->actingAs($admin)
        ->get(route('admin.jobs.index', ['category' => 'Engineering']))
        ->assertOk()
        ->assertSee($approved->title)
        ->assertDontSee($pending->title);

    $this->actingAs($admin)
        ->get(route('admin.jobs.index', [
            'sort' => 'employer',
            'direction' => 'asc',
            'per_page' => 25,
        ]))
        ->assertOk()
        ->assertSee('Showing 1-3')
        ->assertSee('of 3');
});

it('lets admins review complete formatted job details and suspend without changing employer content', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create([
        'first_name' => 'Chika',
        'last_name' => 'Employer',
        'email' => 'chika@example.com',
    ]);
    $job = Job::factory()->open()->for($employer, 'employer')->create([
        'title' => 'Senior Nurse',
        'company' => 'Care Point',
        'category' => 'Healthcare',
        'description' => '<h2>Responsibilities</h2><p>Review patient intake and lead ward operations.</p><h3>Requirements</h3><ul><li>Nursing qualification</li></ul><script>alert("unsafe")</script>',
        'location' => 'Abuja',
        'employment_type' => 'full_time',
        'due_date' => today()->addDays(30),
    ]);
    $employerFields = Arr::except($job->refresh()->getAttributes(), ['status', 'updated_at', 'suspended_at', 'suspension_reason', 'suspended_by']);

    $response = $this->actingAs($admin)
        ->get(route('admin.jobs.show', $job))
        ->assertOk()
        ->assertSee('Senior Nurse')
        ->assertSee('Care Point')
        ->assertSee('Healthcare')
        ->assertSee('chika@example.com')
        ->assertSee('Review patient intake and lead ward operations.')
        ->assertSee('<h2>Responsibilities</h2>', false)
        ->assertSee('<li>Nursing qualification</li>', false)
        ->assertSee('Abuja')
        ->assertSee('Full-time')
        ->assertDontSee('alert("unsafe")', false)
        ->assertDontSee('Edit Job')
        ->assertDontSee('Approve Job')
        ->assertDontSee('name="status"', false);

    $document = adminJobListingsDocument($response->getContent());
    $buttonLabels = [];
    foreach ($document->query('//button') as $button) {
        $buttonLabels[] = trim(preg_replace('/\s+/', ' ', $button->textContent));
    }
    expect($buttonLabels)->toContain('Confirm suspension', 'Delete permanently');

    $this->actingAs($admin)
        ->from(route('admin.jobs.show', $job))
        ->patch(route('admin.jobs.suspend', $job), [
            'suspension_reason' => '  Remove misleading eligibility requirements.  ',
        ])
        ->assertRedirect(route('admin.jobs.show', $job))
        ->assertSessionHas('success');

    $job->refresh();
    expect(Arr::only($job->getAttributes(), array_keys($employerFields)))->toBe($employerFields)
        ->and($job->isSuspended())->toBeTrue()
        ->and($job->suspension_reason)->toBe('Remove misleading eligibility requirements.')
        ->and($job->suspended_by)->toBe($admin->id);

    $this->get(route('Browse-jobs'))
        ->assertOk()
        ->assertDontSee('Senior Nurse');

    $this->actingAs($admin)
        ->get(route('admin.jobs.show', $job))
        ->assertOk()
        ->assertSee('Suspended')
        ->assertSee('Remove misleading eligibility requirements.')
        ->assertSee($admin->first_name)
        ->assertSee('Suspended');

    $this->actingAs($employer)->get(route('jobs.show', $job))->assertOk()
        ->assertSee('Suspended by platform moderation')
        ->assertSee('Remove misleading eligibility requirements.');
});

it('prevents non-admin users from managing jobs through admin routes', function () {
    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $job = Job::factory()->for($employer, 'employer')->create();

    $this->actingAs($employer)
        ->get(route('admin.jobs.index'))
        ->assertForbidden();

    $this->actingAs($applicant)
        ->get(route('admin.jobs.show', $job))
        ->assertForbidden();

    $this->actingAs($employer)
        ->patch(route('admin.jobs.suspend', $job), [
            'suspension_reason' => 'Misleading content.',
        ])
        ->assertForbidden();

    $this->actingAs($applicant)->delete(route('admin.jobs.destroy', $job), ['confirm_deletion' => 1])
        ->assertForbidden();

    foreach ([$employer, $applicant] as $user) {
        expect(Gate::forUser($user)->allows('suspend', $job))->toBeFalse()
            ->and(Gate::forUser($user)->allows('deleteAsModerator', $job))->toBeFalse()
            ->and(Gate::forUser($user)->allows('viewModeration', $job))->toBeFalse();
    }

    expect($job->fresh()->status)->toBe(JobStatus::Open);
});

it('suspends jobs without changing employer content or existing applications', function (JobStatus $status) {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->create(['status' => $status]);
    $application = ApplicationForm::factory()->for($job)->create();

    $this->actingAs($admin)->patchJson(route('admin.jobs.suspend', $job), [
        'suspension_reason' => 'Content violates platform requirements.',
    ])->assertOk()->assertJsonPath('status', 'suspended');

    expect($job->fresh()->status)->toBe(JobStatus::Suspended)
        ->and($job->fresh()->suspendedBy->is($admin))->toBeTrue();
    $this->assertModelExists($application);
})->with([JobStatus::Open, JobStatus::Closed]);

it('validates suspension reasons before changing moderation', function (mixed $reason) {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)->patchJson(route('admin.jobs.suspend', $job), ['suspension_reason' => $reason])
        ->assertUnprocessable()->assertJsonValidationErrors('suspension_reason');

    expect($job->fresh()->isSuspended())->toBeFalse()
        ->and($job->fresh()->status)->toBe(JobStatus::Open);
})->with(['empty' => [''], 'whitespace' => ['   '], 'array' => [['reason']], 'too long' => [str_repeat('x', 2001)]]);

it('rejects content and workflow changes submitted through the suspension endpoint', function (string $field, mixed $value) {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->open()->create();
    $before = $job->refresh()->getAttributes();

    $this->actingAs($admin)->patchJson(route('admin.jobs.suspend', $job), [
        'suspension_reason' => 'Content issue.', $field => $value,
    ])->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($job->fresh()->getAttributes())->toBe($before);
})->with([
    'workflow' => ['status', 'rejected'],
    'title' => ['title', 'Admin rewrite'],
    'description' => ['description', 'Admin rewrite'],
    'ownership' => ['employer_id', 999],
    'moderator' => ['suspended_by', 999],
]);

it('does not overwrite a previous suspension', function () {
    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();
    $job = Job::factory()->open()->create();
    $this->actingAs($firstAdmin)->patchJson(route('admin.jobs.suspend', $job), ['suspension_reason' => 'Original reason.'])->assertOk();
    $before = $job->fresh()->getAttributes();

    $this->actingAs($secondAdmin)->patchJson(route('admin.jobs.suspend', $job), ['suspension_reason' => 'Replacement reason.'])
        ->assertUnprocessable()->assertJsonValidationErrors('suspension_reason');

    expect($job->fresh()->getAttributes())->toBe($before);
});

it('removes admin content editing and status mutation endpoints', function () {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->create();
    $before = $job->refresh()->getAttributes();
    $this->actingAs($admin)->putJson(route('admin.jobs.show', $job), ['title' => 'Rewrite'])->assertStatus(405);
    $this->patchJson(route('admin.jobs.show', $job).'/status', ['status' => 'approved'])->assertNotFound();
    $this->postJson(route('jobs.store'), [])->assertForbidden();
    $this->putJson(route('jobs.update', $job), ['title' => 'Rewrite'])->assertForbidden();
    $this->deleteJson(route('jobs.destroy', $job))->assertForbidden();
    expect($job->fresh()->getAttributes())->toBe($before);
});

it('reopens corrected employer jobs while retaining protected moderation history', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->suspended()->for($employer, 'employer')->create([
        'suspended_at' => now(), 'suspension_reason' => 'Policy issue.', 'suspended_by' => $admin->id,
    ]);
    $suspendedAt = $job->suspended_at;
    $this->actingAs($job->employer)->putJson(route('jobs.update', $job), [
        'title' => 'Revised employer title', 'description' => '<p>Revised requirements.</p>',
        'company' => $job->company, 'category' => $job->category,
        'location' => 'Lagos', 'employment_type' => 'full_time',
        'start_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(),
        'status' => 'approved', 'suspended_at' => null, 'suspended_by' => null, 'suspension_reason' => null,
    ])->assertOk();

    $job->refresh();
    expect($job->title)->toBe('Revised employer title')
        ->and($job->status)->toBe(JobStatus::Open)
        ->and($job->isSuspended())->toBeFalse()
        ->and($job->suspended_at->eq($suspendedAt))->toBeTrue()
        ->and($job->suspended_by)->toBe($admin->id)
        ->and($job->suspension_reason)->toBe('Policy issue.');
});

it('hides suspended jobs from public discovery and closes application access', function () {
    $job = Job::factory()->suspended()->create([
        'title' => 'Suspended Moderation Fixture', 'due_date' => today()->addDays(30),
        'suspended_at' => now(), 'suspension_reason' => 'Policy issue.',
    ]);
    expect(Job::query()->open()->whereKey($job)->exists())->toBeFalse()
        ->and(Job::query()->active()->whereKey($job)->exists())->toBeFalse()
        ->and(Job::query()->acceptingApplications()->whereKey($job)->exists())->toBeFalse()
        ->and($job->isAcceptingApplications())->toBeFalse();

    foreach (['client.jobs-listings', 'Browse-jobs'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee($job->title);
    }
    $this->get(route('job-details', $job))->assertNotFound();
    $candidate = User::factory()->applicant()->create();
    $this->actingAs($candidate)->get(route('job-details', $job))->assertNotFound();
    $this->get(route('applications.create', $job))->assertNotFound();
    $this->postJson(route('applications.store', $job), [])->assertForbidden();
    $this->assertDatabaseMissing('application_forms', ['job_id' => $job->id]);
});

it('rechecks a stale job before storing application files or records', function () {
    app(OrganizationContext::class)->set(null, true);
    Storage::fake('public');
    $job = Job::factory()->open()->create(['due_date' => today()->addDays(30)]);
    $candidate = User::factory()->applicant()->create();
    Job::query()->whereKey($job)->update(['status' => JobStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => 'Policy issue.']);

    expect(fn () => app(ApplicationFormService::class)->submit($job, $candidate, []))
        ->toThrow(ValidationException::class, 'This job is not accepting applications.');
    $this->assertDatabaseMissing('application_forms', ['job_id' => $job->id]);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('filters job status and safely handles malformed query values', function () {
    $admin = User::factory()->admin()->create();
    $suspended = Job::factory()->suspended()->create(['title' => 'Suspended Content Fixture', 'suspended_at' => now(), 'suspension_reason' => 'Misleading content.']);
    $clear = Job::factory()->create(['title' => 'Clear Content Fixture']);
    $this->actingAs($admin)->get(route('suspended-jobs'))->assertOk()
        ->assertSee($suspended->title)->assertSee($suspended->suspension_reason)->assertDontSee($clear->title);
    $this->get(route('admin.jobs.index', ['status' => 'open']))->assertOk()
        ->assertSee($clear->title)->assertDontSee($suspended->title);
    $this->get(route('admin.jobs.index', ['search' => ['bad'], 'category' => ['bad'], 'moderation' => ['bad'], 'status' => ['bad'], 'sort' => ['bad']]))
        ->assertOk();
});

it('requires explicit confirmation before permanently deleting a job and its logo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('job-logos/moderation.png', 'fixture');
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->create(['logo' => 'job-logos/moderation.png']);
    $application = ApplicationForm::factory()->for($job)->create();
    $this->actingAs($admin)->deleteJson(route('admin.jobs.destroy', $job))->assertUnprocessable()
        ->assertJsonValidationErrors('confirm_deletion');
    $this->assertModelExists($job);
    Storage::disk('public')->assertExists('job-logos/moderation.png');

    $this->delete(route('admin.jobs.destroy', $job), ['confirm_deletion' => 1])->assertRedirect(route('admin.jobs.index'))->assertSessionHas('success');
    $this->assertModelMissing($job);
    $this->assertModelMissing($application);
    Storage::disk('public')->assertMissing('job-logos/moderation.png');
});

it('retains server validation feedback and safely renders suspension reasons', function () {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->create();
    $this->actingAs($admin)->from(route('admin.jobs.show', $job))->patch(route('admin.jobs.suspend', $job), [
        'moderation_job' => $job->id, 'moderation_action' => 'suspend', 'suspension_reason' => ['invalid'],
    ])->assertRedirect(route('admin.jobs.show', $job))->assertSessionHasErrors('suspension_reason');
    $this->get(route('admin.jobs.show', $job))->assertOk()->assertSee('aria-invalid="true"', false);

    $reason = '<script>alert("unsafe reason")</script>';
    $job->forceFill(['suspended_at' => now(), 'suspension_reason' => $reason])->save();
    $this->get(route('admin.jobs.show', $job))->assertOk()->assertSee($reason)->assertDontSee($reason, false);
});

it('renders each responsive job row once with labelled content and unique moderation dialogs', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create(['email' => str_repeat('e', 180).'@example.com']);
    $job = Job::factory()->for($employer, 'employer')->create([
        'title' => str_repeat('T', 255), 'company' => str_repeat('C', 255),
        'location' => str_repeat('L', 255), 'category' => str_repeat('X', 255),
        'status' => JobStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => str_repeat('Reason ', 285),
    ]);
    $otherJob = Job::factory()->for($employer, 'employer')->create();
    $response = $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk();
    $document = adminJobListingsDocument($response->getContent());
    $table = $document->query('//table[@role="table"]')->item(0);

    expect($table)->not->toBeNull()
        ->and($document->query('.//tr[@data-job-row]', $table)->length)->toBe(2)
        ->and($document->query('.//th[@scope="col"]', $table)->length)->toBe(6);

    foreach ([$job, $otherJob] as $listedJob) {
        $rows = $document->query('.//tr[@data-job-row="'.$listedJob->id.'"]', $table);
        expect($rows->length)->toBe(1);
        $row = $rows->item(0);
        expect($document->query('./td[@role="cell" and @headers]', $row)->length)->toBe(6)
            ->and($document->query('.//dialog', $row)->length)->toBe(2);

        foreach ($document->query('./td[@headers]', $row) as $cell) {
            expect($document->query('.//th[@id="'.$cell->getAttribute('headers').'"]', $table)->length)->toBe(1);
        }

        foreach ($document->query('.//dialog[@aria-labelledby]', $row) as $dialog) {
            expect($document->query('//*[@id="'.$dialog->getAttribute('aria-labelledby').'"]')->length)->toBe(1);
        }

        $forms = $document->query('.//form[@method="POST"]', $row);
        expect($forms->length)->toBe(2);
        foreach ($forms as $form) {
            expect($document->query('.//input[@name="_token"]', $form)->length)->toBe(1)
                ->and($document->query('.//input[@name="title" or @name="status"]', $form)->length)->toBe(0);
        }
    }

    $response->assertSee($job->title)->assertSee($job->company)->assertSee($job->location)
        ->assertSee($employer->email)->assertSee($job->suspension_reason);
    expect($document->query('//div[@role="region" and @aria-label="Employer job listings" and @tabindex="0"]')->length)->toBe(1);
});

it('preserves filters and mobile sorting through compact pagination at every page boundary', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    Job::factory()->count(25)->for($employer, 'employer')->sequence(fn (Sequence $sequence): array => [
        'title' => sprintf('Responsive Job %03d', $sequence->index), 'category' => 'Technology',
        'status' => JobStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => 'Policy issue.',
    ])->create();
    $filters = [
        'search' => 'Responsive', 'category' => 'Technology', 'status' => 'suspended',
        'sort' => 'title', 'direction' => 'asc',
    ];
    $response = $this->actingAs($admin)->get(route('admin.jobs.index', [...$filters, 'page' => 2]))->assertOk()
        ->assertSee('Page 2 of 3')->assertSee('Showing 11-20 of 25');
    $document = adminJobListingsDocument($response->getContent());
    $pagination = $document->query('//nav[@aria-label="Job listings pagination"]')->item(0);
    $form = $document->query('//form[@aria-label="Filter and sort job listings"]')->item(0);
    expect($form)->not->toBeNull()
        ->and($document->query('.//select[@name="sort"]/option[@selected]', $form)->item(0)->getAttribute('value'))->toBe('title')
        ->and($document->query('.//select[@name="direction"]/option[@selected]', $form)->item(0)->getAttribute('value'))->toBe('asc')
        ->and($document->query('//tr[@data-job-row]')->length)->toBe(10);
    $titles = [];
    foreach ($document->query('//tr[@data-job-row]/td[1]/a') as $title) {
        $titles[] = trim($title->textContent);
    }
    expect($titles[0])->toBe('Responsive Job 010')->and($titles[9])->toBe('Responsive Job 019');

    foreach (['prev' => '1', 'next' => '3'] as $direction => $page) {
        $link = $document->query('.//a[@rel="'.$direction.'"]', $pagination)->item(0);
        $query = [];
        parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
        expect($query)->toMatchArray([...$filters, 'page' => $page]);
    }

    $firstPage = $this->get(route('admin.jobs.index', $filters))->assertOk();
    $firstDocument = adminJobListingsDocument($firstPage->getContent());
    expect($firstDocument->query('//nav[@aria-label="Job listings pagination"]//a[@rel="prev"]')->length)->toBe(0)
        ->and($firstDocument->query('//nav[@aria-label="Job listings pagination"]//span[@aria-disabled="true"]')->length)->toBe(1);
    $lastPage = $this->get(route('admin.jobs.index', [...$filters, 'page' => 3]))->assertOk()->assertSee('Page 3 of 3');
    $lastDocument = adminJobListingsDocument($lastPage->getContent());
    expect($lastDocument->query('//nav[@aria-label="Job listings pagination"]//a[@rel="next"]')->length)->toBe(0)
        ->and($lastDocument->query('//nav[@aria-label="Job listings pagination"]//span[@aria-disabled="true"]')->length)->toBe(1);

    $outOfRangePage = $this->get(route('admin.jobs.index', [...$filters, 'page' => PHP_INT_MAX]))
        ->assertOk()->assertSee('Return to first page');
    $outOfRangeDocument = adminJobListingsDocument($outOfRangePage->getContent());
    expect($outOfRangeDocument->query('//nav[@aria-label="Job listings pagination"]//a')->length)->toBe(1);
});

it('shows an accessible empty state without pagination when no jobs match', function () {
    $admin = User::factory()->admin()->create();
    Job::factory()->create(['title' => 'Other role']);
    $response = $this->actingAs($admin)->get(route('admin.jobs.index', ['search' => 'NoMatchingFixture']))
        ->assertOk()->assertSee('No jobs match these filters.')->assertSee('Showing 0-0 of 0');
    $document = adminJobListingsDocument($response->getContent());
    expect($document->query('//tr[@data-job-row]')->length)->toBe(0)
        ->and($document->query('//table//td[@colspan="6" and @role="cell"]')->length)->toBe(1)
        ->and($document->query('//nav[@aria-label="Job listings pagination"]')->length)->toBe(0);
});
