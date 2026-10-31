<?php

use App\Services\JobCountryService;
use App\Services\LocationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Nnjeim\World\CountryFlag;
use Nnjeim\World\Models\Country;

class JobReferenceCountry extends Country
{
    public function getTable(): string
    {
        return 'job_reference_countries';
    }
}

beforeEach(function () {
    config([
        'database.connections.job_country_reference' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
        'world.connection' => 'job_country_reference',
        'world.models.countries' => JobReferenceCountry::class,
    ]);
    DB::purge('job_country_reference');
    $model = new JobReferenceCountry;
    $model->getConnection()->getSchemaBuilder()->create($model->getTable(), function (Blueprint $table) {
        $table->id();
        $table->string('iso2', 2);
        $table->string('name');
    });
    $model->newQuery()->insert([
        ['iso2' => 'NG', 'name' => 'Nigeria'],
        ['iso2' => 'ZM', 'name' => 'Zambia'],
    ]);
    app()->forgetInstance(JobCountryService::class);
});

afterEach(fn () => DB::purge('job_country_reference'));

test('job countries use the configured world model and connection with one shared query', function () {
    $connection = DB::connection('job_country_reference');
    $connection->enableQueryLog();
    $countries = app(JobCountryService::class);

    expect(array_keys($countries->all()))->toBe(['NG', 'ZM']);
    expect($countries->find(' ng ')['name'])->toBe('Nigeria');
    expect($countries->find('nigeria')['code'])->toBe('NG');
    expect(app(LocationService::class)->jobCountries())->toBe([
        ['value' => 'NG', 'label' => 'Nigeria'],
        ['value' => 'ZM', 'label' => 'Zambia'],
    ]);

    $selects = collect($connection->getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from "job_reference_countries"'),
    );
    expect($selects)->toHaveCount(1);
});

test('world countries have map coordinates and local flags without optional database columns', function () {
    $countries = app(JobCountryService::class);
    $nigeria = $countries->find('NG');
    $zambia = $countries->find('Zambia');

    expect($nigeria['latitude'])->toBeFloat()->not->toBeNull();
    expect($nigeria['longitude'])->toBeFloat()->not->toBeNull();
    expect($nigeria['flag_url'])->toBe(asset('asset/marthire/flags/ng.svg'));
    expect($zambia['flag_url'])->toBe(asset('asset/marthire/flags/zm.svg'));
    expect($zambia['flag_emoji'])->toBe(CountryFlag::emoji('ZM'));
    // Bundled metadata enriches existing rows without adding selectable countries.
    expect($countries->find('US'))->toBeNull();
});

test('missing world country tables do not create fallback job country options', function () {
    (new JobReferenceCountry)->getConnection()->getSchemaBuilder()->drop('job_reference_countries');

    expect(app(JobCountryService::class)->all())->toBe([]);
    expect(app(JobCountryService::class)->find('NG'))->toBeNull();
    expect(app(LocationService::class)->jobCountries())->toBe([]);
});

test('country flag URLs normalize ISO2 codes and reject unsafe asset paths', function () {
    $countries = app(JobCountryService::class);

    expect($countries->flagUrl(' NG '))->toBe(asset('asset/marthire/flags/ng.svg'));
    foreach ([null, '', 'NGA', '../ng', 'ng/../../us', '<script>', '12'] as $code) {
        expect($countries->flagUrl($code))->toBeNull();
    }
});

test('every supported World country has a valid local lowercase SVG flag', function () {
    $path = dirname((new ReflectionClass(Country::class))->getFileName(), 3).'/resources/json/countries.json';
    $countries = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $missing = [];
    $invalid = [];
    $previous = libxml_use_internal_errors(true);

    try {
        foreach ($countries as $country) {
            $code = strtolower(trim($country['iso2']));
            $flag = public_path('asset/marthire/flags/'.$code.'.svg');
            if (! is_file($flag)) {
                $missing[] = $code;

                continue;
            }
            $svg = new DOMDocument;
            if (! $svg->loadXML(file_get_contents($flag), LIBXML_NONET)
                || $svg->documentElement?->localName !== 'svg'
                || $svg->documentElement?->namespaceURI !== 'http://www.w3.org/2000/svg') {
                $invalid[] = $code;
            }
            libxml_clear_errors();
        }
    } finally {
        libxml_use_internal_errors($previous);
    }

    expect($countries)->not->toBeEmpty();
    expect($missing)->toBe([]);
    expect($invalid)->toBe([]);
});
