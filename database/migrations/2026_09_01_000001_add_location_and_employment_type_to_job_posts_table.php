<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_posts', 'location')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->string('location')->nullable()->after('category');
            });
        }

        if (! Schema::hasColumn('job_posts', 'employment_type')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->string('employment_type', 50)->nullable()->after('location');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_posts', 'employment_type')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->dropColumn('employment_type');
            });
        }

        if (Schema::hasColumn('job_posts', 'location')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
