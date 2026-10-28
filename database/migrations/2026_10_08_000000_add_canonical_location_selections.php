<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds canonical country / state / city selection columns (aligned with the
 * nnjeim/world data set) alongside the existing free-text legacy columns
 * (nationality, state_of_origin, local_government_area), which are retained.
 *
 * Legacy selections are backfilled only when they resolve unambiguously to a
 * known country. Local government areas are never converted to cities, so the
 * canonical "city" column is left null for legacy Nigerian records.
 */
return new class extends Migration
{
    /**
     * Canonical nnjeim/world state names for Nigeria, used to normalise legacy
     * free-text state_of_origin values without depending on seeded world data.
     *
     * @var list<string>
     */
    private const NIGERIA_STATES = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
        'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'Gombe', 'Imo', 'Jigawa',
        'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger',
        'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ];

    /**
     * Country demonym / common-name aliases that resolve to an ISO2 code and
     * canonical country name. Intentionally conservative: only unambiguous
     * values are backfilled.
     *
     * @var array<string, array{code: string, name: string}>
     */
    private const COUNTRY_ALIASES = [
        'nigeria' => ['code' => 'NG', 'name' => 'Nigeria'],
        'nigerian' => ['code' => 'NG', 'name' => 'Nigeria'],
    ];

    public function up(): void
    {
        $this->addSelectionColumns('users', true);
        $this->addSelectionColumns('application_forms', false);

        $this->backfill('users');
        $this->backfill('application_forms');
    }

    /**
     * Only the world-specific metadata columns are reversible; the canonical
     * country/state name selections are retained so downgrades keep the data
     * the application now reads from.
     */
    public function down(): void
    {
        foreach (['users', 'application_forms'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $drop = array_values(array_filter(
                ['country_code', 'city', 'location_confirmed_at'],
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($drop !== []) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($drop));
            }
        }
    }

    private function addSelectionColumns(string $table, bool $withConfirmedAt): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table, $withConfirmedAt): void {
            if (! Schema::hasColumn($table, 'country_code')) {
                $t->string('country_code', 2)->nullable();
            }
            if (! Schema::hasColumn($table, 'country')) {
                $t->string('country')->nullable();
            }
            if (! Schema::hasColumn($table, 'state')) {
                $t->string('state')->nullable();
            }
            if (! Schema::hasColumn($table, 'city')) {
                $t->string('city')->nullable();
            }
            if ($withConfirmedAt && ! Schema::hasColumn($table, 'location_confirmed_at')) {
                $t->timestamp('location_confirmed_at')->nullable();
            }
        });
    }

    private function backfill(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'country_code')) {
            return;
        }

        $rows = DB::table($table)
            ->whereNull('country_code')
            ->select('id', 'nationality', 'state_of_origin')
            ->get();

        foreach ($rows as $row) {
            $country = self::COUNTRY_ALIASES[mb_strtolower(trim((string) ($row->nationality ?? '')))] ?? null;

            if ($country === null) {
                continue;
            }

            $update = [
                'country_code' => $country['code'],
                'country' => $country['name'],
            ];

            $state = $this->canonicalNigeriaState((string) ($row->state_of_origin ?? ''));

            if ($state !== null) {
                $update['state'] = $state;
            }

            DB::table($table)->where('id', $row->id)->update($update);
        }
    }

    private function canonicalNigeriaState(string $input): ?string
    {
        $key = mb_strtolower(trim($input));

        if ($key === '') {
            return null;
        }

        $aliases = [
            'fct' => 'Abuja Federal Capital Territory',
            'f.c.t' => 'Abuja Federal Capital Territory',
            'f.c.t.' => 'Abuja Federal Capital Territory',
            'fct abuja' => 'Abuja Federal Capital Territory',
            'abuja' => 'Abuja Federal Capital Territory',
            'federal capital territory' => 'Abuja Federal Capital Territory',
            'abuja federal capital territory' => 'Abuja Federal Capital Territory',
        ];

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        foreach (self::NIGERIA_STATES as $state) {
            if (mb_strtolower($state) === $key) {
                return $state;
            }
        }

        return null;
    }
};
