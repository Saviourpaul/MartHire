<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_posts')) {
            return;
        }

        if (! Schema::hasColumn('job_posts', 'category')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->string('category')->nullable()->after('company');
            });
        }

        if (Schema::hasColumn('job_posts', 'status')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->change();
            });

            DB::table('job_posts')->where('status', 'active')->update(['status' => 'approved']);
            DB::table('job_posts')->where('status', 'inactive')->update(['status' => 'rejected']);
            DB::table('job_posts')
                ->whereNotIn('status', ['pending', 'approved', 'rejected'])
                ->update(['status' => 'pending']);
        }

        if (! Schema::hasIndex('job_posts', 'job_posts_category_created_at_idx')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->index(['category', 'created_at'], 'job_posts_category_created_at_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_posts')) {
            return;
        }

        if (Schema::hasIndex('job_posts', 'job_posts_category_created_at_idx')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->dropIndex('job_posts_category_created_at_idx');
            });
        }

        if (Schema::hasColumn('job_posts', 'status')) {
            DB::table('job_posts')->where('status', 'approved')->update(['status' => 'active']);
            DB::table('job_posts')
                ->whereIn('status', ['pending', 'rejected'])
                ->update(['status' => 'inactive']);

            Schema::table('job_posts', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active')->change();
            });
        }

        if (Schema::hasColumn('job_posts', 'category')) {
            Schema::table('job_posts', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
