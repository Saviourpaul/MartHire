<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
   
    public function up(): void
    {
        if (Schema::hasColumn('job_posts', 'slug')) {
            $jobs = DB::table('job_posts')
                ->whereNull('slug')
                ->orWhere('slug', '')
                ->get();

            foreach ($jobs as $job) {
                $slug = $this->generateUniqueSlug($job->title);
                DB::table('job_posts')
                    ->where('id', $job->id)
                    ->update(['slug' => $slug]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
   
        DB::table('job_posts')->update(['slug' => null]);
    }

    /**
     * Generate a unique slug for a job title
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = $base = Str::slug($title);
        $i = 1;

        while (DB::table('job_posts')->where('slug', $slug)->exists()) {
            $slug = "{$base}-" . $i++;
        }

        return $slug;
    }
};
