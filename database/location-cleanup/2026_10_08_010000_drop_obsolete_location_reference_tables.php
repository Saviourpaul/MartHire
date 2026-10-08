<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole() || getenv('LOCATION_REFERENCE_BACKUP_CONFIRMED') !== '1' || getenv('LOCATION_DEPLOYMENT_VERIFIED') !== '1') {
            throw new RuntimeException('Back up location reference tables and verify deployment before explicit cleanup. See docs/location-deployment.md.');
        }
        foreach (['users', 'application_forms'] as $table) {
            foreach (['country_code', 'country', 'state', 'city'] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException('Canonical location migration has not completed.');
                }
            }
        }
        // Refuse to disable foreign keys: an unexpected external dependency must stop cleanup.
        foreach (['nigeria_local_government_areas', 'nigeria_states', 'cities', 'states', 'countries', 'timezones', 'currencies', 'languages'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restore removed reference data from the pre-cleanup backup. No user/application records were removed.');
    }
};
