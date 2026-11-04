<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('candidate_job_preferences', 'preferred_workplace')) {
            Schema::table('candidate_job_preferences', function (Blueprint $table) {
                $table->string('preferred_workplace', 24)->nullable()->after('preferred_job_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('candidate_job_preferences', 'preferred_workplace')) {
            Schema::table('candidate_job_preferences', function (Blueprint $table) {
                $table->dropColumn('preferred_workplace');
            });
        }
    }
};
