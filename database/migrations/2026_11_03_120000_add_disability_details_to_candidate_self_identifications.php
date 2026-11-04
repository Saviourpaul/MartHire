<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_self_identifications')) {
            return;
        }

        if (! Schema::hasColumn('candidate_self_identifications', 'disability_details')) {
            Schema::table('candidate_self_identifications', function (Blueprint $table): void {
                $table->text('disability_details')->nullable()->after('disability_status');
            });
        }

    }

    public function down(): void
    {
        if (! Schema::hasTable('candidate_self_identifications')) {
            return;
        }

        if (Schema::hasColumn('candidate_self_identifications', 'disability_details')) {
            Schema::table('candidate_self_identifications', function (Blueprint $table): void {
                $table->dropColumn('disability_details');
            });
        }
    }
};
