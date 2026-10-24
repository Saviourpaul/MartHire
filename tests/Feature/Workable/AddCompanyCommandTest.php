<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const ACC_PROMPT = 'Workable API access token (input hidden; leave blank for redirect-only)';

beforeEach(function () {
    config(['workable.fake' => false]);
});

function accFakePublished(): void
{
    Http::fake([
        'pilotco.workable.com/spi/v3/jobs*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/workable/jobs_published.json')), true)
        ),
    ]);
}

it('creates a company with an encrypted token from the secret prompt', function () {
    accFakePublished();

    $this->artisan('workable:add-company', ['subdomain' => 'pilotco', '--name' => 'Pilot Co'])
        ->expectsQuestion(ACC_PROMPT, 'tok_123')
        ->assertSuccessful();

    $company = Company::where('subdomain', 'pilotco')->firstOrFail();
    expect($company->name)->toBe('Pilot Co')
        ->and($company->api_token)->toBe('tok_123')
        ->and($company->credentials_status)->toBe('valid');

    $raw = DB::table('companies')->where('id', $company->id)->value('api_token');
    expect($raw)->not->toBe('tok_123');
});

it('creates a redirect-only unverified company when the token is blank', function () {
    Http::fake();

    $this->artisan('workable:add-company', ['subdomain' => 'pilotco', '--apply-mode' => 'native'])
        ->expectsQuestion(ACC_PROMPT, '')
        ->assertSuccessful();

    $company = Company::where('subdomain', 'pilotco')->firstOrFail();
    expect($company->credentials_status)->toBe('unverified')
        ->and($company->apply_mode)->toBe('redirect')
        ->and($company->api_token)->toBeNull();
    Http::assertNothingSent();
});

it('never writes the token to output', function () {
    accFakePublished();

    $this->artisan('workable:add-company', ['subdomain' => 'pilotco', '--name' => 'Pilot Co'])
        ->expectsQuestion(ACC_PROMPT, 'tok_123')
        ->expectsOutputToContain('pilotco')
        ->assertSuccessful();

    expect(Artisan::output())->not->toContain('tok_123');
});

it('sets credentials invalid on a rejected token', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response([], 401)]);

    $this->artisan('workable:add-company', ['subdomain' => 'pilotco'])
        ->expectsQuestion(ACC_PROMPT, 'bad')
        ->assertSuccessful();

    expect(Company::where('subdomain', 'pilotco')->value('credentials_status'))->toBe('invalid');
});
