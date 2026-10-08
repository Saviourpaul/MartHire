<?php

use App\Models\User;
use App\Services\LocationSnapshots;
use App\Services\WorldLocationService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->locationCacheDirectory = storage_path('framework/testing/locations-'.bin2hex(random_bytes(8)));
    config(['locations.cache.path' => $this->locationCacheDirectory, 'locations.cache.lock_path' => $this->locationCacheDirectory]);
    Http::preventStrayRequests();
});

afterEach(function () {
    File::deleteDirectory($this->locationCacheDirectory);
});

function fakeWorldCatalog(array $overrides = []): void
{
    Http::fake([
        '*/countries*' => Http::response(['success' => true, 'data' => [['id' => 160, 'name' => 'Nigeria', 'iso2' => 'NG']]]),
        '*/states*' => Http::response(['success' => true, 'data' => [['id' => 3007, 'name' => 'Lagos', 'country_code' => 'NG']]]),
        '*/cities*' => Http::response(['success' => true, 'data' => [['id' => 79256, 'name' => 'Ikeja', 'state_id' => 3007, 'country_code' => 'NG', 'state' => ['id' => 3007, 'name' => 'Lagos']]]]),
        ...$overrides,
    ]);
}

it('uses live IDs internally and exposes only normalized values', function () {
    fakeWorldCatalog();
    $this->getJson('/api/locations/cities?country=NG&state=Lagos')
        ->assertOk()->assertExactJson(['data' => [['value' => 'Ikeja', 'label' => 'Ikeja']], 'meta' => ['available' => true, 'empty' => false, 'source' => 'api', 'fetched_at' => now()->timestamp]]);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/cities') && $request['filters']['state_id'] === 3007
        && $request['filters']['country_code'] === 'NG' && str_contains($request['fields'], 'state') && $request->hasHeader('Accept-Language', 'en'));
    expect(app(WorldLocationService::class)->validateSelection('NG', 'Lagos', 'Ikeja'))->toBe(['country_code' => 'NG', 'country' => 'Nigeria', 'state' => 'Lagos', 'city' => 'Ikeja']);
    Http::assertSentCount(3);
});

it('falls back on failure and never sends a snapshot state ID upstream', function () {
    Http::fake(['*' => Http::response([], 503)]);
    $this->getJson('/api/locations/cities?country=NG&state=Lagos')
        ->assertOk()->assertJsonPath('meta.source', 'snapshot')->assertJsonFragment(['value' => 'Ikeja', 'label' => 'Ikeja']);
    Http::assertSentCount(1);
    $this->travel(61)->seconds();
    $this->getJson('/api/locations/cities?country=NG&state=Lagos')->assertOk();
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/cities'));
});

it('rejects bad payloads without caching them and applies a cooldown', function ($payload, $status) {
    Http::fake(['*' => Http::response($payload, $status)]);
    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'snapshot');
    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'snapshot');
    Http::assertSentCount(1);
})->with([
    'rate limit' => [[], 429], 'server failure' => [[], 500], 'malformed JSON' => ['{broken', 200],
    'wrong envelope' => [['success' => false, 'data' => []], 200],
    'unexpected empty' => [['success' => true, 'data' => []], 200],
    'bad ISO2' => [['success' => true, 'data' => [['name' => 'Nigeria', 'iso2' => 'Nigeria']]], 200],
    'missing label' => [['success' => true, 'data' => [['iso2' => 'NG']]], 200],
]);

it('handles connection timeout with bundled fallback', function () {
    Http::fake(fn () => throw new ConnectionException('private SMTP/database/transport details'));
    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'snapshot')->assertDontSee('private');
});

it('rejects states and cities belonging to the wrong parent', function ($tier) {
    fakeWorldCatalog([$tier === 'states' ? '*/states*' : '*/cities*' => Http::response(['success' => true, 'data' => $tier === 'states'
        ? [['id' => 3007, 'name' => 'Lagos', 'country_code' => 'US']]
        : [['name' => 'Wrong city', 'country_code' => 'NG', 'state_id' => 3007, 'state' => ['name' => 'Abia']]]])]);
    $url = $tier === 'states' ? '/api/locations/states?country=NG' : '/api/locations/cities?country=NG&state=Lagos';
    $this->getJson($url)->assertOk()->assertJsonPath('meta.source', 'snapshot')->assertDontSee('Wrong city');
})->with(['states', 'cities']);

it('refreshes after 24 hours, uses last good data for 90 days, then snapshots', function () {
    fakeWorldCatalog();
    $service = app(WorldLocationService::class);
    expect($service->countries()['meta']['source'])->toBe('api');
    $this->travel(23)->hours();
    expect($service->countries()['meta']['source'])->toBe('cache');
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([], 503)]);
    $this->travel(2)->hours();
    expect($service->countries()['meta']['source'])->toBe('stale');
    $this->travel(91)->days();
    expect($service->countries()['meta']['source'])->toBe('snapshot');
});

it('works with an unavailable main database and normal database session/cache settings', function () {
    config(['cache.default' => 'database', 'session.driver' => 'database']);
    $kernel = app(Kernel::class);
    $database = DB::getFacadeRoot();
    DB::shouldReceive('connection')->andReturnUsing(fn () => throw new RuntimeException('main database down'));
    Http::fake(['*' => Http::response([], 503)]);
    try {
        foreach (['countries' => 200, 'states?country=NG' => 200, 'cities?country=NG&state=Lagos' => 200,
            'states?country=bad' => 422, 'cities?country=NG&state=Bad' => 422] as $path => $status) {
            // Use actual kernel requests: the test helper's URL preparation may resolve a session.
            $request = Request::create('https://localhost/api/locations/'.$path, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);
            expect($response->getStatusCode())->toBe($status)
                ->and($response->headers->has('Set-Cookie'))->toBeFalse();
        }
        config(['locations.requests_per_minute' => 5]);
        $request = Request::create('https://localhost/api/locations/countries', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        expect($kernel->handle($request)->getStatusCode())->toBe(429);
        config(['locations.requests_per_minute' => 60, 'locations.snapshot_path' => $this->locationCacheDirectory.'/missing']);
        expect($kernel->handle($request)->getStatusCode())->toBe(503);
    } finally {
        DB::swap($database);
    }
});

it('uses snapshots when independent cache reads and locks fail', function () {
    config(['locations.cache.driver' => 'not-a-cache-driver']);
    $this->getJson('/api/locations/cities?country=NG&state=Lagos')->assertOk()->assertJsonPath('meta.source', 'snapshot');
    Http::assertNothingSent();
});

it('still returns validated API data when cache writes fail', function () {
    $repository = Mockery::mock(app(WorldLocationService::class)->cache())->makePartial();
    $repository->shouldReceive('put')->andThrow(new RuntimeException('private file system error'));
    Cache::partialMock()->shouldReceive('build')->andReturn($repository);
    fakeWorldCatalog();

    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'api')->assertDontSee('private');
    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'api');
    Http::assertSentCount(2);
});

it('rejects a corrupted cache entry instead of trusting its options', function () {
    $service = app(WorldLocationService::class);
    $key = 'world:v1:'.hash('sha256', json_encode([config('locations.base_url'), 'countries', null, null]));
    $service->cache()->put($key, ['at' => now()->timestamp, 'data' => [['value' => 'not-ISO2', 'label' => 'Untrusted country']]], 86400);
    fakeWorldCatalog();

    $this->getJson('/api/locations/countries')->assertOk()->assertJsonPath('meta.source', 'api')->assertDontSee('Untrusted country');
    Http::assertSentCount(1);
});

it('rejects concurrent client limiter updates without touching the database', function () {
    $key = 'location-client:'.hash('sha256', '127.0.0.1');
    $lock = app(WorldLocationService::class)->cache()->lock($key.':lock', 5);
    expect($lock->get())->toBeTrue();
    try {
        $this->getJson('/api/locations/countries')->assertStatus(429)->assertHeader('Retry-After', '1');
        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

it('returns friendly 503 when no data source is available', function () {
    config(['locations.snapshot_path' => $this->locationCacheDirectory.'/missing']);
    Http::fake(['*' => Http::response([], 500)]);
    $this->getJson('/api/locations/countries')->assertStatus(503)->assertJsonPath('meta.available', false)
        ->assertJsonPath('message', 'Location lists are temporarily unavailable. Please try again shortly.')->assertDontSee('Exception');
});

it('enforces ISO2 input, parent membership and stateless file-backed throttling', function () {
    Http::fake(['*' => Http::response([], 503)]);
    $this->getJson('/api/locations/states?country=https://evil.test')->assertUnprocessable();
    $this->getJson('/api/locations/states?country=ZZ')->assertUnprocessable();
    $this->getJson('/api/locations/cities?country=NG&state=Not-a-state')->assertUnprocessable();
    config(['locations.requests_per_minute' => 3]);
    $this->getJson('/api/locations/countries')->assertStatus(429)->assertHeader('Retry-After');
});

it('requires only tiers present in the verified snapshot catalog', function () {
    Http::fake(['*' => Http::response([], 503)]);
    $service = app(WorldLocationService::class);
    $snapshots = app(LocationSnapshots::class);
    $emptyCountry = collect($snapshots->countries())->first(fn ($row) => $snapshots->states($row['value']) === []);
    expect($emptyCountry)->not->toBeNull();
    expect($service->validateSelection($emptyCountry['value'], null, null))->toMatchArray(['state' => null, 'city' => null]);
    expect(fn () => $service->validateSelection('NG', null, null))->toThrow(ValidationException::class);
    expect(fn () => $service->validateSelection('NG', 'Lagos', 'Not-a-city'))->toThrow(ValidationException::class);
});

it('accepts a successful empty live tier only when known data corroborates it', function () {
    Http::fake([
        '*/countries*' => Http::response(['success' => true, 'data' => [['id' => 1, 'name' => 'Vatican City State (Holy See)', 'iso2' => 'VA']]]),
        '*/states*' => Http::response(['success' => true, 'data' => []]),
    ]);

    $this->getJson('/api/locations/states?country=VA')->assertOk()->assertJsonPath('meta.source', 'api')->assertJsonPath('meta.empty', true);
    expect(app(WorldLocationService::class)->validateSelection('VA', null, null))->toMatchArray(['country_code' => 'VA', 'state' => null, 'city' => null]);
    Http::assertSentCount(2);
});

it('confirms locations with genuinely empty cities but rejects invented cities', function () {
    Http::fake(['*' => Http::response([], 503)]);
    $snapshots = app(LocationSnapshots::class);
    $emptyState = collect($snapshots->states('GB'))->first(fn ($row): bool => $snapshots->cities('GB', $row['value']) === []);
    expect($emptyState)->not->toBeNull();
    $user = User::factory()->completeApplicantProfile()->create();
    $payload = ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email,
        'phone' => $user->phone, 'address' => $user->address, 'date_of_birth' => $user->date_of_birth->format('Y-m-d'),
        'country_code' => 'GB', 'state' => $emptyState['value'], 'city' => null];

    $this->actingAs($user)->patch('/profile', $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($user->fresh())->country_code->toBe('GB')->state->toBe($emptyState['value'])->city->toBeNull()
        ->location_confirmed_at->not->toBeNull()->hasCompletedApplicantProfile()->toBeTrue();
    $this->actingAs($user)->patch('/profile', [...$payload, 'city' => 'Invented city'])->assertSessionHasErrors('city');
});

it('does not save or confirm a profile when every catalog source is unavailable', function () {
    config(['locations.snapshot_path' => $this->locationCacheDirectory.'/missing']);
    Http::fake(['*' => Http::response([], 503)]);
    $user = User::factory()->completeApplicantProfile()->create(['location_confirmed_at' => null]);
    $payload = ['first_name' => 'Changed name', 'last_name' => $user->last_name, 'email' => $user->email,
        'phone' => $user->phone, 'address' => $user->address, 'date_of_birth' => $user->date_of_birth->format('Y-m-d'),
        'country_code' => 'NG', 'state' => 'Lagos', 'city' => 'Ikeja'];

    $this->actingAs($user)->patch('/profile', $payload)->assertSessionHasErrors('country_code');
    expect($user->fresh())->first_name->toBe($user->first_name)->location_confirmed_at->toBeNull();
});

it('preserves completed legacy profiles and confirms canonical edits without rewriting history', function () {
    Http::fake(['*' => Http::response([], 503)]);
    $user = User::factory()->completeApplicantProfile()->create(['country_code' => null, 'country' => null, 'state' => null, 'city' => null, 'location_confirmed_at' => null,
        'nationality' => 'Nigerian', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Old LGA']);
    expect($user->hasCompletedApplicantProfile())->toBeTrue();
    $payload = ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email,
        'phone' => $user->phone, 'address' => $user->address, 'date_of_birth' => $user->date_of_birth->format('Y-m-d'),
        'country_code' => 'NG', 'state' => 'Lagos', 'city' => 'Ikeja', 'location_confirmed_at' => '1999-01-01', 'country' => 'Spoofed country'];
    $this->actingAs($user)->patch('/profile', $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($user->fresh())->country->toBe('Nigeria')->nationality->toBe('Nigerian')->local_government_area->toBe('Old LGA')
        ->location_confirmed_at->not->toBeNull();
    $this->actingAs($user)->patch('/profile', [...$payload, 'city' => 'Old LGA'])->assertSessionHasErrors('city');
});
