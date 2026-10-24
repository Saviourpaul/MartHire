<?php

use App\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeCompany(array $overrides = []): Company
{
    return Company::create(array_merge([
        'name' => 'Acme',
        'slug' => 'acme',
        'subdomain' => 'acme',
        'api_token' => 'secret-xyz',
    ], $overrides));
}

it('stores the api token encrypted at rest', function () {
    $c = makeCompany();

    $raw = DB::table('companies')->where('id', $c->id)->value('api_token');

    expect($raw)->not->toBe('secret-xyz')
        ->and($raw)->not->toContain('secret-xyz')
        ->and($c->fresh()->api_token)->toBe('secret-xyz');
});

it('hides the api token from array and json', function () {
    $c = makeCompany();

    expect(array_key_exists('api_token', $c->toArray()))->toBeFalse()
        ->and(str_contains($c->toJson(), 'secret-xyz'))->toBeFalse();
});

it('enforces a unique subdomain', function () {
    makeCompany();

    expect(fn () => makeCompany(['slug' => 'other']))->toThrow(QueryException::class);
});

it('applies defaults', function () {
    $c = makeCompany()->fresh();

    expect($c->apply_mode)->toBe('redirect')
        ->and($c->credentials_status)->toBe('unverified');
});
