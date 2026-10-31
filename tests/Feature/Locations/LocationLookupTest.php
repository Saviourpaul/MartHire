<?php

use App\Exceptions\LocationDataUnavailableException;
use App\Http\Requests\Concerns\ValidatesLocationSelection;
use App\Models\User;
use App\Services\LocationService;
use App\Support\WorldLocations;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

class LookupCountry extends Country
{
    public function getTable(): string
    {
        return 'lookup_countries';
    }
}

class LookupState extends State
{
    public function getTable(): string
    {
        return 'lookup_states';
    }
}

class LookupCity extends City
{
    public function getTable(): string
    {
        return 'lookup_cities';
    }
}

class FailingLookupState extends LookupState
{
    public function newQuery()
    {
        throw new RuntimeException('Simulated reference query failure.');
    }
}

beforeEach(function () {
    config([
        'database.connections.location_reference' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        'world.connection' => 'location_reference',
        'world.models.countries' => LookupCountry::class,
        'world.models.states' => LookupState::class,
        'world.models.cities' => LookupCity::class,
        'locations.cache.driver' => 'array',
    ]);
    DB::purge('location_reference');
    Cache::store('array')->flush();
    $schema = DB::connection('location_reference')->getSchemaBuilder();
    $schema->create('lookup_countries', function (Blueprint $table) {
        $table->id();
        $table->string('iso2', 2);
        $table->string('name');
    });
    $schema->create('lookup_states', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('country_id');
        $table->string('name');
    });
    $schema->create('lookup_cities', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('country_id');
        $table->unsignedBigInteger('state_id');
        $table->string('name');
    });
    LookupCountry::query()->insert([
        ['id' => 101, 'iso2' => 'GB', 'name' => 'United Kingdom'],
        ['id' => 102, 'iso2' => 'US', 'name' => 'United States'],
        ['id' => 103, 'iso2' => 'AA', 'name' => 'Country Without Regions'],
    ]);
    LookupState::query()->insert([
        ['id' => 301, 'country_id' => 101, 'name' => 'Shared'],
        ['id' => 302, 'country_id' => 102, 'name' => 'Shared'],
        ['id' => 303, 'country_id' => 101, 'name' => 'Empty'],
        ['id' => 304, 'country_id' => 102, 'name' => 'Foreign Region'],
    ]);
    LookupCity::query()->insert([
        ['id' => 401, 'country_id' => 101, 'state_id' => 301, 'name' => 'British City'],
        ['id' => 402, 'country_id' => 102, 'state_id' => 302, 'name' => 'American City'],
        ['id' => 403, 'country_id' => 102, 'state_id' => 301, 'name' => 'Mismatched Parent'],
        ['id' => 404, 'country_id' => 101, 'state_id' => 301, 'name' => 'British City'],
    ]);
    $this->actingAs(User::factory()->create());
});

function locationSelectionValidator(array $input): Illuminate\Validation\Validator
{
    $request = new class($input) extends FormRequest
    {
        use ValidatesLocationSelection;

        public function rules(): array
        {
            return $this->locationSelectionRules(['required']);
        }
    };

    return Validator::make($input, $request->rules());
}

test('location endpoints respect configured World models connection and table names', function () {
    $this->getJson(route('locations.states', ['country' => 'GB']))->assertOk()->assertExactJson([
        'data' => [['value' => 'Empty', 'label' => 'Empty'], ['value' => 'Shared', 'label' => 'Shared']],
        'meta' => ['manual' => false],
    ]);
    $this->getJson(route('locations.cities', ['country' => 'GB', 'state' => 'Shared']))->assertOk()->assertExactJson([
        'data' => [['value' => 'British City', 'label' => 'British City']],
        'meta' => ['manual' => false],
    ]);
    expect(app(LocationService::class)->countries())->toContain(['value' => 'GB', 'label' => 'United Kingdom']);
});

test('state lookups do not depend on installing the city table', function () {
    DB::connection('location_reference')->getSchemaBuilder()->drop('lookup_cities');
    $this->getJson(route('locations.states', ['country' => 'GB']))->assertOk()->assertJsonPath('meta.manual', false);
    $this->getJson(route('locations.cities', ['country' => 'GB', 'state' => 'Shared']))->assertStatus(503)->assertJsonMissingPath('meta.manual');
});

test('city and state validation enforce both selected parents', function () {
    $locations = app(LocationService::class);
    expect(WorldLocations::cityExists('GB', 'Shared', 'British City'))->toBeTrue()
        ->and(WorldLocations::cityExists('GB', 'Shared', 'American City'))->toBeFalse()
        ->and($locations->cityIsValid('GB', 'Shared', 'Mismatched Parent'))->toBeFalse()
        ->and($locations->stateIsValid('GB', 'Foreign Region'))->toBeFalse();
    $this->getJson(route('locations.cities', ['country' => 'GB', 'state' => 'Foreign Region']))->assertUnprocessable()->assertJsonValidationErrors('state');
    $validator = locationSelectionValidator(['country_code' => 'GB', 'country' => 'United Kingdom', 'state' => 'Shared', 'city' => 'American City']);
    expect($validator->fails())->toBeTrue()->and($validator->errors()->has('city'))->toBeTrue();
});

test('manual city entry is authorized only for a valid parent with no reference cities', function () {
    $locations = app(LocationService::class);
    $this->getJson(route('locations.cities', ['country' => 'GB', 'state' => 'Empty']))->assertOk()->assertExactJson(['data' => [], 'meta' => ['manual' => true]]);
    expect($locations->cityIsValid('GB', 'Empty', 'An unlisted city'))->toBeTrue()
        ->and($locations->cityIsValid('GB', 'Shared', 'An unlisted city'))->toBeFalse();
    $validator = locationSelectionValidator(['country_code' => 'GB', 'country' => 'United Kingdom', 'state' => 'Empty', 'city' => 'An unlisted city']);
    expect($validator->passes())->toBeTrue();
});

test('manual state entry requires an installed valid country with no reference states', function () {
    $this->getJson(route('locations.states', ['country' => 'AA']))->assertOk()->assertExactJson(['data' => [], 'meta' => ['manual' => true]]);
    $this->getJson(route('locations.cities', ['country' => 'AA', 'state' => 'Unlisted region']))->assertOk()->assertExactJson(['data' => [], 'meta' => ['manual' => true]]);
    expect(app(LocationService::class)->cityIsValid('AA', 'Unlisted region', 'Unlisted city'))->toBeTrue();
    $this->getJson(route('locations.states', ['country' => 'ZZ']))->assertUnprocessable()->assertJsonValidationErrors('country');
});

test('unavailable or incomplete reference tables never authorize arbitrary locations', function (bool $missing) {
    $schema = DB::connection('location_reference')->getSchemaBuilder();
    if ($missing) {
        $schema->drop('lookup_states');
    } else {
        $schema->table('lookup_states', fn (Blueprint $table) => $table->dropColumn('name'));
    }
    $this->getJson(route('locations.states', ['country' => 'GB']))->assertStatus(503)->assertJsonMissingPath('meta.manual');
    $validator = locationSelectionValidator(['country_code' => 'GB', 'country' => 'United Kingdom', 'state' => 'Made up', 'city' => 'Made up']);
    expect($validator->fails())->toBeTrue()->and($validator->errors()->has('state'))->toBeTrue();
    expect(fn () => app(LocationService::class)->stateIsValid('GB', 'Made up'))->toThrow(LocationDataUnavailableException::class);
})->with(['missing table' => [true], 'incomplete schema' => [false]]);

test('reference query failures return unavailable instead of enabling manual entry', function () {
    config(['world.models.states' => FailingLookupState::class]);
    $this->getJson(route('locations.states', ['country' => 'GB']))->assertStatus(503)->assertJsonMissingPath('meta.manual');
    expect(fn () => app(LocationService::class)->stateIsValid('GB', 'Made up'))->toThrow(LocationDataUnavailableException::class);
});

test('initially empty option lists are not cached across reference data becoming available', function () {
    $locations = app(LocationService::class);
    expect($locations->cityOptions('GB', 'Empty')['meta']['manual'])->toBeTrue();
    LookupCity::query()->create(['country_id' => 101, 'state_id' => 303, 'name' => 'New City']);
    expect($locations->cityOptions('GB', 'Empty'))->toBe([
        'data' => [['value' => 'New City', 'label' => 'New City']], 'meta' => ['manual' => false],
    ]);
});

test('Nigeria always uses canonical states and LGAs without World tables', function () {
    $schema = DB::connection('location_reference')->getSchemaBuilder();
    foreach (['lookup_cities', 'lookup_states', 'lookup_countries'] as $table) {
        $schema->drop($table);
    }
    $this->getJson(route('locations.states', ['country' => 'NG']))->assertOk()->assertJsonCount(37, 'data')->assertJsonPath('meta.manual', false);
    $this->getJson(route('locations.cities', ['country' => 'NG', 'state' => 'Lagos']))->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.manual', false);
    $this->getJson(route('locations.cities', ['country' => 'NG', 'state' => 'Invalid']))->assertUnprocessable();
    expect(app(LocationService::class)->cityIsValid('NG', 'Lagos', 'Ikeja'))->toBeTrue();
});

test('location query validation rejects arrays and malformed parent values', function () {
    $this->getJson(route('locations.states', ['country' => ['GB']]))->assertUnprocessable()->assertJsonValidationErrors('country');
    $this->getJson(route('locations.cities', ['country' => 'GB', 'state' => ['Shared']]))->assertUnprocessable()->assertJsonValidationErrors('state');
});
