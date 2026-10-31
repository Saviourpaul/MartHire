<?php

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\JobPosting;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\JobCatalog;
use App\Support\OrganizationContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;

beforeEach(function () {
    // World reference tables are installed separately from application migrations.
    // Keep this fixture on the isolated application test connection.
    config(['world.connection' => config('database.default')]);
    $countryClass = config('world.models.countries');
    $country = new $countryClass;
    $schema = $country->getConnection()->getSchemaBuilder();

    if (! $schema->hasTable($country->getTable())) {
        $schema->create($country->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('name');
            $table->tinyInteger('status')->default(1);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
    }

    foreach ([
        ['iso2' => 'NG', 'name' => 'Nigeria', 'latitude' => '9.0820', 'longitude' => '8.6753'],
        ['iso2' => 'GB', 'name' => 'United Kingdom', 'latitude' => '54.0000', 'longitude' => '-2.5000'],
    ] as $attributes) {
        $country->newQuery()->updateOrCreate(['iso2' => $attributes['iso2']], $attributes);
    }
});

function publicVisibilityJobPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Native Public Backend Engineer',
        'description' => '<p>Build and maintain services for our customers.</p>',
        'company' => 'Public Visibility Co',
        'category' => 'Engineering',
        'location' => 'Remote',
        'country_code' => 'NG',
        'employment_type' => 'full_time',
        'start_date' => today()->toDateString(),
        'due_date' => today()->addDays(30)->toDateString(),
        'action' => 'publish',
    ], $overrides);
}

function publicVisibilityCreateJob(Organization $organization, User $owner, array $overrides = []): Job
{
    $attributes = publicVisibilityJobPayload($overrides);
    unset($attributes['action']);

    return app(OrganizationContext::class)->runInOrganization($organization, fn () => $owner->jobs()->create([
        ...$attributes,
        'organization_id' => $organization->id,
        'status' => $overrides['status'] ?? JobStatus::Open,
    ]));
}

test('publishing an organization job makes it visible to guests alongside Workable jobs', function () {
    [$organization, $owner] = organizationWithOwner();
    $external = JobPosting::factory()->create(['title' => 'Workable Public Designer']);

    $this->actingAs($owner)
        ->post(route('organisations.jobs.store'), publicVisibilityJobPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $native = Job::withoutGlobalScope(OrganizationScope::class)
        ->where('organization_id', $organization->id)->firstOrFail();

    expect($native->country_code)->toBe('NG');
    $this->post(route('logout'))->assertRedirect();

    foreach ([route('home'), route('workable-jobs.index')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee($native->title)
            ->assertSee($external->title)
            ->assertSee(route('jobs.show', $native), false)
            ->assertSee(route('workable-jobs.show', $external->shortcode), false);
    }
});

test('native job detail and application links keep the native job destination', function () {
    [$organization, $owner] = organizationWithOwner();
    $native = publicVisibilityCreateJob($organization, $owner);
    $applicant = User::factory()->create();

    $this->get(route('jobs.show', $native))
        ->assertOk()
        ->assertSee($native->title)
        ->assertDontSee(route('workable-jobs.apply', $native->slug), false);

    $this->actingAs($applicant)->get(route('jobs.show', $native))
        ->assertOk()
        ->assertSee(route('applications.create', $native), false)
        ->assertDontSee(route('workable-jobs.apply', $native->slug), false);

    $this->get(route('applications.create', $native))
        ->assertOk()
        ->assertSee($native->title);
});

test('an explicit native job country supplies its country flag and hero map marker', function () {
    [$organization, $owner] = organizationWithOwner();
    publicVisibilityCreateJob($organization, $owner, ['location' => 'Remote', 'country_code' => 'NG']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Nigeria')
        ->assertSee('asset/marthire/flags/ng.svg', false)
        ->assertSee('data-pin', false);
});

test('native slugs and Workable shortcodes can coincide without losing either source', function () {
    [$organization, $owner] = organizationWithOwner();
    $native = publicVisibilityCreateJob($organization, $owner, ['title' => 'Native Collision Engineer']);
    $native->forceFill(['slug' => 'COLLIDE1'])->save();
    $external = JobPosting::factory()->create([
        'shortcode' => 'COLLIDE1',
        'title' => 'Workable Collision Designer',
    ]);

    $this->get(route('workable-jobs.index'))->assertOk()
        ->assertSee(route('jobs.show', $native), false)
        ->assertSee(route('workable-jobs.show', $external->shortcode), false);

    $this->get(route('jobs.show', $native))->assertOk()->assertSee($external->title);
    $this->get(route('workable-jobs.show', $external->shortcode))->assertOk()->assertSee($native->title);
});

test('the selected country takes precedence over a conflicting free text city', function () {
    [$organization, $owner] = organizationWithOwner();
    publicVisibilityCreateJob($organization, $owner, ['location' => 'Lagos', 'country_code' => 'GB']);

    $response = $this->get(route('home'))->assertOk()
        ->assertSee('United Kingdom')
        ->assertSee('asset/marthire/flags/gb.svg', false)
        ->assertDontSee('asset/marthire/flags/ng.svg', false);
    $pins = JobCatalog::make($response->viewData('jobs'))->pins();

    expect($pins)->toHaveCount(1)
        ->and($pins->first()['code'])->toBe('gb')
        ->and($pins->first()['cities'])->toBe([]);
});

test('a World country outside the old map list gets bundled coordinates and a local SVG flag', function () {
    $countryClass = config('world.models.countries');
    $country = new $countryClass;
    $country->getConnection()->getSchemaBuilder()->table($country->getTable(), function (Blueprint $table) {
        $table->dropColumn(['latitude', 'longitude']);
    });
    $countryClass::query()->create(['iso2' => 'KY', 'name' => 'Cayman Islands']);
    [$organization, $owner] = organizationWithOwner();
    publicVisibilityCreateJob($organization, $owner, ['country_code' => 'KY']);

    $response = $this->get(route('home'))->assertOk()
        ->assertSee('Cayman Islands')
        ->assertSee('data-pin', false)
        ->assertSee('asset/marthire/flags/ky.svg', false);
    $pins = JobCatalog::make($response->viewData('jobs'))->pins();

    expect($pins)->toHaveCount(1)
        ->and($pins->first()['code'])->toBe('ky')
        ->and($pins->first()['lat'])->toBeNumeric()
        ->and($pins->first()['lon'])->toBeNumeric();
});

test('native jobs remain publicly visible through their deadline day', function () {
    [$organization, $owner] = organizationWithOwner();
    $native = publicVisibilityCreateJob($organization, $owner, ['due_date' => today()->toDateString()]);

    foreach ([route('home'), route('workable-jobs.index'), route('jobs.show', $native)] as $url) {
        $this->get($url)->assertOk()->assertSee($native->title);
    }
});

test('unpublished and expired native jobs stay out of public feeds and detail pages', function (string $status, bool $expired) {
    [$organization, $owner] = organizationWithOwner();
    $native = publicVisibilityCreateJob($organization, $owner, [
        'title' => 'Private Native Role '.ucfirst($status).($expired ? ' Expired' : ''),
        'status' => JobStatus::from($status),
        'start_date' => today()->subDays(30)->toDateString(),
        'due_date' => $expired ? today()->subDay()->toDateString() : today()->addDays(30)->toDateString(),
    ]);

    foreach ([route('home'), route('workable-jobs.index')] as $url) {
        $this->get($url)->assertOk()->assertDontSee($native->title);
    }

    $this->get(route('jobs.show', $native))->assertNotFound();
})->with([
    'draft' => ['draft', false],
    'suspended' => ['suspended', false],
    'closed' => ['closed', false],
    'expired' => ['open', true],
]);

test('organization viewers can browse public native jobs from other organizations', function () {
    [$firstOrganization, $firstOwner] = organizationWithOwner();
    [$secondOrganization, $secondOwner] = organizationWithOwner();
    $firstJob = publicVisibilityCreateJob($firstOrganization, $firstOwner, ['title' => 'First Organization Public Role']);
    $secondJob = publicVisibilityCreateJob($secondOrganization, $secondOwner, ['title' => 'Second Organization Public Role', 'country_code' => 'GB']);

    $this->actingAs($firstOwner);

    foreach ([route('home'), route('workable-jobs.index')] as $url) {
        $this->get($url)->assertOk()->assertSee($firstJob->title)->assertSee($secondJob->title);
    }

    $this->get(route('jobs.show', $secondJob))->assertOk()->assertSee($secondJob->title);
});

test('native public slugs stay unique when different organizations publish the same title', function () {
    [$firstOrganization, $firstOwner] = organizationWithOwner();
    [$secondOrganization, $secondOwner] = organizationWithOwner();
    $first = publicVisibilityCreateJob($firstOrganization, $firstOwner, ['title' => 'Shared Job Title']);
    $second = publicVisibilityCreateJob($secondOrganization, $secondOwner, ['title' => 'Shared Job Title']);

    expect($first->slug)->not->toBe($second->slug);
    $this->get(route('jobs.show', $first))->assertOk();
    $this->get(route('jobs.show', $second))->assertOk();
});

test('an undated native job cannot accept public applications', function () {
    $job = new Job(['status' => JobStatus::Open, 'due_date' => null]);

    expect($job->isAcceptingApplications())->toBeFalse();
});

test('an uploaded job logo appears on public cards and details and the edit form', function () {
    Storage::fake('public');
    [$organization, $owner] = organizationWithOwner();
    $logo = UploadedFile::fake()->createWithContent('company-logo.png', file_get_contents(base_path('tests/Fixtures/applications/profile.png')));

    $this->actingAs($owner)
        ->post(route('organisations.jobs.store'), publicVisibilityJobPayload(['logo' => $logo]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $job = Job::withoutGlobalScope(OrganizationScope::class)->where('organization_id', $organization->id)->firstOrFail();
    Storage::disk('public')->assertExists($job->logo);
    $logoUrl = asset('storage/'.$job->logo);

    $this->get(route('organisations.jobs.edit', $job))->assertOk()->assertSee('preview: '.Js::from($logoUrl), false);
    $this->post(route('logout'))->assertRedirect();

    foreach ([route('home'), route('jobs.index'), route('jobs.show', $job)] as $url) {
        $this->get($url)->assertOk()->assertSee('src="'.$logoUrl.'"', false)->assertSee('nw-monogram__image', false);
    }
});

test('replacing a job logo updates the public image and removes the old upload', function () {
    Storage::fake('public');
    [$organization, $owner] = organizationWithOwner();
    Storage::disk('public')->put('job-logos/previous.png', file_get_contents(base_path('tests/Fixtures/applications/profile.png')));
    $job = publicVisibilityCreateJob($organization, $owner, ['logo' => 'job-logos/previous.png']);
    $replacement = UploadedFile::fake()->createWithContent('replacement.png', file_get_contents(base_path('tests/Fixtures/applications/profile.png')));

    $this->actingAs($owner)
        ->patch(route('organisations.jobs.update', $job), publicVisibilityJobPayload(['logo' => $replacement]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $job->refresh();
    Storage::disk('public')->assertMissing('job-logos/previous.png');
    Storage::disk('public')->assertExists($job->logo);
    $logoUrl = asset('storage/'.$job->logo);
    $this->post(route('logout'))->assertRedirect();
    $this->get(route('jobs.show', $job))->assertOk()->assertSee('src="'.$logoUrl.'"', false)->assertDontSee('storage/job-logos/previous.png', false);
});

test('absent or missing job logos keep company initials without a broken default image', function () {
    Storage::fake('public');
    [$organization, $owner] = organizationWithOwner();
    $job = publicVisibilityCreateJob($organization, $owner, ['logo' => 'job-logos/missing.png']);

    expect((new Job)->logoUrl())->toBeNull()->and($job->logoUrl())->toBeNull();
    $this->get(route('jobs.show', $job))->assertOk()
        ->assertSee('nw-monogram', false)
        ->assertSee('PV')
        ->assertDontSee('nw-monogram__image', false)
        ->assertDontSee('assets/images/brands/company-logo-1.png', false);
});
