<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_posts', 'workplace_type')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->string('workplace_type', 24)->nullable()->after('employment_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_posts', 'workplace_type')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->dropColumn('workplace_type');
            });
        }
    }
};
