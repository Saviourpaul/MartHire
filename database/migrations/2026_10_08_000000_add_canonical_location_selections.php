<?php

use App\Services\LocationSnapshots;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $snapshots = new LocationSnapshots;
        $countries = $snapshots->countries();
        foreach (['users', 'application_forms'] as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            foreach (['country_code', 'country', 'state', 'city'] as $column) {
                if (! Schema::hasColumn($name, $column)) {
                    Schema::table($name, fn (Blueprint $table) => $table->string($column, $column === 'country_code' ? 2 : 255)->nullable());
                }
            }
            if ($name === 'users' && ! Schema::hasColumn($name, 'location_confirmed_at')) {
                Schema::table($name, fn (Blueprint $table) => $table->timestamp('location_confirmed_at')->nullable());
            }
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($name, $snapshots, $countries) {
                foreach ($rows as $row) {
                    $legacy = trim($row->country ?: ($row->nationality ?? ''));
                    if (strcasecmp($legacy, 'Nigerian') === 0) {
                        $legacy = 'Nigeria';
                    }
                    $matches = array_values(array_filter(
                        $countries,
                        fn ($country) => filled($row->country_code) ? $country['value'] === $row->country_code : strcasecmp($country['label'], $legacy) === 0
                    ));
                    if (count($matches) !== 1) {
                        continue;
                    }
                    $country = $matches[0];
                    if (filled($row->country_code) && filled($row->country) && strcasecmp(trim($row->country), $country['label']) !== 0) {
                        // Conflicting canonical values are not an unambiguous backfill source.
                        continue;
                    }
                    $updates = [];
                    if (blank($row->country_code)) {
                        $updates['country_code'] = $country['value'];
                    }
                    if (blank($row->country)) {
                        $updates['country'] = $country['label'];
                    }
                    if (blank($row->state) && filled($row->state_of_origin ?? null)) {
                        $state = trim($row->state_of_origin);
                        if ($country['value'] === 'NG' && in_array(strtolower($state), ['fct', 'abuja', 'federal capital territory'], true)) {
                            $state = 'Abuja Federal Capital Territory';
                        }
                        $states = array_values(array_filter($snapshots->states($country['value']), fn ($option) => strcasecmp($option['value'], $state) === 0));
                        if (count($states) === 1) {
                            $updates['state'] = $states[0]['value'];
                        }
                    }
                    if ($updates !== []) {
                        DB::table($name)->where('id', $row->id)->update($updates);
                    }
                    // Neither LGAs nor historical names constitute a confirmed city selection.
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally retain canonical and historical values on rollback: do not destroy user data.
    }
};
