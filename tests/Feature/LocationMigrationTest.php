<?php

use App\Models\ApplicationForm;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates canonical selections without reference-table foreign keys on a fresh schema', function () {
    foreach (['users', 'application_forms'] as $table) {
        foreach (['country_code', 'country', 'state', 'city'] as $column) {
            expect(Schema::hasColumn($table, $column))->toBeTrue();
        }
        expect(collect(Schema::getForeignKeys($table))->pluck('foreign_table')->intersect(['countries', 'states', 'cities', 'nigeria_states'])->all())->toBe([]);
    }
    expect(Schema::hasColumn('users', 'location_confirmed_at'))->toBeTrue();
    foreach (['countries', 'states', 'cities', 'nigeria_states', 'nigeria_local_government_areas'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

it('backfills only unambiguous legacy selections and never converts LGAs to cities', function () {
    $legacy = User::factory()->create(['nationality' => 'Nigerian', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Ikeja']);
    $unknown = User::factory()->create(['nationality' => 'Unrecognised', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Ikeja']);
    $canonical = User::factory()->create(['country_code' => 'CA', 'country' => 'Canada', 'state' => 'Ontario', 'city' => 'Toronto',
        'nationality' => 'Nigeria', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Ikeja']);
    $conflicting = User::factory()->create(['country_code' => 'NG', 'country' => 'Canada', 'state_of_origin' => 'Lagos']);
    $application = ApplicationForm::factory()->create(['country_code' => null, 'country' => null, 'state' => null, 'city' => null,
        'nationality' => 'Nigeria', 'state_of_origin' => 'FCT', 'local_government_area' => 'Bwari']);
    $migration = require database_path('migrations/2026_10_08_000000_add_canonical_location_selections.php');
    $migration->up();
    $migration->up();
    expect($legacy->fresh())->country_code->toBe('NG')->country->toBe('Nigeria')->state->toBe('Lagos')
        ->city->toBeNull()->location_confirmed_at->toBeNull()->local_government_area->toBe('Ikeja');
    expect($unknown->fresh())->country_code->toBeNull()->state->toBeNull();
    expect($canonical->fresh())->country_code->toBe('CA')->country->toBe('Canada')->state->toBe('Ontario')->city->toBe('Toronto');
    expect($conflicting->fresh())->country_code->toBe('NG')->country->toBe('Canada')->state->toBeNull()->city->toBeNull();
    expect($application->fresh())->country_code->toBe('NG')->state->toBe('Abuja Federal Capital Territory')->city->toBeNull()->local_government_area->toBe('Bwari');
    $count = User::count();
    $migration->down();
    expect(User::count())->toBe($count)->and($legacy->fresh()->country)->toBe('Nigeria');
});

it('adds missing selection columns on existing legacy-only tables', function () {
    $legacy = User::factory()->create(['nationality' => 'Nigeria', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Ikeja']);
    Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['country_code', 'country', 'state', 'city', 'location_confirmed_at']));
    Schema::table('application_forms', fn (Blueprint $table) => $table->dropColumn(['country_code', 'country', 'state', 'city']));
    (require database_path('migrations/2026_10_08_000000_add_canonical_location_selections.php'))->up();
    expect($legacy->fresh())->country_code->toBe('NG')->state->toBe('Lagos')->city->toBeNull();
});

it('requires backup and deployment confirmation before reference cleanup', function () {
    $oldBackupFlag = getenv('LOCATION_REFERENCE_BACKUP_CONFIRMED');
    $oldDeploymentFlag = getenv('LOCATION_DEPLOYMENT_VERIFIED');
    putenv('LOCATION_REFERENCE_BACKUP_CONFIRMED');
    putenv('LOCATION_DEPLOYMENT_VERIFIED');
    Schema::create('nigeria_states', fn (Blueprint $table) => $table->id());
    try {
        $migration = require database_path('location-cleanup/2026_10_08_010000_drop_obsolete_location_reference_tables.php');
        expect(fn () => $migration->up())->toThrow(RuntimeException::class);
        expect(Schema::hasTable('nigeria_states'))->toBeTrue();
    } finally {
        putenv($oldBackupFlag === false ? 'LOCATION_REFERENCE_BACKUP_CONFIRMED' : 'LOCATION_REFERENCE_BACKUP_CONFIRMED='.$oldBackupFlag);
        putenv($oldDeploymentFlag === false ? 'LOCATION_DEPLOYMENT_VERIFIED' : 'LOCATION_DEPLOYMENT_VERIFIED='.$oldDeploymentFlag);
        Schema::dropIfExists('nigeria_states');
    }
});

it('cleans up only obsolete references and keeps application data in an isolated test database', function () {
    $user = User::factory()->create(['nationality' => 'Nigeria', 'local_government_area' => 'Historical LGA']);
    $tables = ['nigeria_local_government_areas', 'nigeria_states', 'cities', 'states', 'countries', 'timezones', 'currencies', 'languages'];
    foreach ($tables as $table) {
        Schema::create($table, fn (Blueprint $schema) => $schema->id());
        DB::table($table)->insert(['id' => 1]);
    }
    $oldBackupFlag = getenv('LOCATION_REFERENCE_BACKUP_CONFIRMED');
    $oldDeploymentFlag = getenv('LOCATION_DEPLOYMENT_VERIFIED');
    putenv('LOCATION_REFERENCE_BACKUP_CONFIRMED=1');
    putenv('LOCATION_DEPLOYMENT_VERIFIED=1');
    try {
        $migration = require database_path('location-cleanup/2026_10_08_010000_drop_obsolete_location_reference_tables.php');
        $migration->up();
        $migration->up();
        foreach ($tables as $table) {
            expect(Schema::hasTable($table))->toBeFalse();
        }
        expect($user->fresh())->nationality->toBe('Nigeria')->local_government_area->toBe('Historical LGA');
        expect(Schema::hasTable('application_forms'))->toBeTrue();
    } finally {
        putenv($oldBackupFlag === false ? 'LOCATION_REFERENCE_BACKUP_CONFIRMED' : 'LOCATION_REFERENCE_BACKUP_CONFIRMED='.$oldBackupFlag);
        putenv($oldDeploymentFlag === false ? 'LOCATION_DEPLOYMENT_VERIFIED' : 'LOCATION_DEPLOYMENT_VERIFIED='.$oldDeploymentFlag);
    }
});
