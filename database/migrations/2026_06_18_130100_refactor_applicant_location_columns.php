<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'nationality')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('nationality')->nullable()->after('address');
                });
            }

            if (! Schema::hasColumn('users', 'state_of_origin')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('state_of_origin')->nullable()->after('nationality');
                });
            }

            if (! Schema::hasColumn('users', 'local_government_area')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('local_government_area')->nullable()->after('state_of_origin');
                });
            }

            if (Schema::hasColumn('users', 'country')) {
                DB::table('users')
                    ->whereNull('nationality')
                    ->update(['nationality' => DB::raw('country')]);
            }

            if (Schema::hasColumn('users', 'state')) {
                DB::table('users')
                    ->whereNull('state_of_origin')
                    ->update(['state_of_origin' => DB::raw('state')]);
            }

            if (Schema::hasColumn('users', 'city')) {
                DB::table('users')
                    ->whereNull('local_government_area')
                    ->update(['local_government_area' => DB::raw('city')]);
            }

            $this->dropColumnsIfTheyExist('users', ['country', 'state', 'city']);
        }

        if (Schema::hasTable('application_forms')) {
            if (Schema::hasColumn('application_forms', 'country')) {
                DB::table('application_forms')
                    ->whereNull('nationality')
                    ->update(['nationality' => DB::raw('country')]);
            }

            if (Schema::hasColumn('application_forms', 'state')) {
                DB::table('application_forms')
                    ->whereNull('state_of_origin')
                    ->update(['state_of_origin' => DB::raw('state')]);
            }

            if (Schema::hasColumn('application_forms', 'city')) {
                DB::table('application_forms')
                    ->whereNull('local_government_area')
                    ->update(['local_government_area' => DB::raw('city')]);
            }

            $this->dropColumnsIfTheyExist('application_forms', ['country', 'state', 'city']);
        }

        Schema::withoutForeignKeyConstraints(function () {
            Schema::dropIfExists('city');
            Schema::dropIfExists('cities');
            Schema::dropIfExists('country');
            Schema::dropIfExists('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('application_forms')) {
            if (! Schema::hasColumn('application_forms', 'country')) {
                Schema::table('application_forms', function (Blueprint $table) {
                    $table->string('country')->nullable()->after('address');
                });
            }

            if (! Schema::hasColumn('application_forms', 'state')) {
                Schema::table('application_forms', function (Blueprint $table) {
                    $table->string('state')->nullable()->after('country');
                });
            }

            if (! Schema::hasColumn('application_forms', 'city')) {
                Schema::table('application_forms', function (Blueprint $table) {
                    $table->string('city')->nullable()->after('state');
                });
            }

            DB::table('application_forms')
                ->whereNull('country')
                ->update(['country' => DB::raw('nationality')]);

            DB::table('application_forms')
                ->whereNull('state')
                ->update(['state' => DB::raw('state_of_origin')]);

            DB::table('application_forms')
                ->whereNull('city')
                ->update(['city' => DB::raw('local_government_area')]);
        }

        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'country')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('country')->nullable()->after('address');
                });
            }

            if (! Schema::hasColumn('users', 'state')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('state')->nullable()->after('country');
                });
            }

            if (! Schema::hasColumn('users', 'city')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('city')->nullable()->after('state');
                });
            }

            DB::table('users')
                ->whereNull('country')
                ->update(['country' => DB::raw('nationality')]);

            DB::table('users')
                ->whereNull('state')
                ->update(['state' => DB::raw('state_of_origin')]);

            DB::table('users')
                ->whereNull('city')
                ->update(['city' => DB::raw('local_government_area')]);

            $this->dropColumnsIfTheyExist('users', ['nationality', 'state_of_origin', 'local_government_area']);
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropColumnsIfTheyExist(string $table, array $columns): void
    {
        $columns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
