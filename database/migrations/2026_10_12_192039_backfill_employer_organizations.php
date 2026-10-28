<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $employers = DB::table('users')
                ->where('role', 'employer')
                ->orWhereIn('id', DB::table('job_posts')->select('employer_id'))
                ->orderBy('id');

            foreach ($employers->cursor() as $employer) {
                $organizationId = DB::table('organization_members')
                    ->where('user_id', $employer->id)
                    ->where('role', 'owner')
                    ->whereNull('deleted_at')
                    ->value('organization_id');

                if ($organizationId === null) {
                    $organizationId = DB::table('organizations')->insertGetId([
                        'name' => mb_substr(trim($employer->first_name.' '.$employer->last_name).' Organization', 0, 255),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('organization_members')->insert([
                        'organization_id' => $organizationId,
                        'user_id' => $employer->id,
                        'role' => 'owner',
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);
                }

                DB::table('job_posts')->where('employer_id', $employer->id)
                    ->whereNull('organization_id')->update(['organization_id' => $organizationId]);
            }

            foreach (DB::table('job_posts')->select(['id', 'organization_id'])->orderBy('id')->cursor() as $job) {
                DB::table('application_forms')->where('job_id', $job->id)
                    ->whereNull('organization_id')->update(['organization_id' => $job->organization_id]);
            }

            foreach (DB::table('application_forms')->select(['id', 'organization_id'])->orderBy('id')->cursor() as $application) {
                DB::table('application_documents')->where('application_form_id', $application->id)
                    ->whereNull('organization_id')->update(['organization_id' => $application->organization_id]);
                DB::table('application_status_histories')->where('application_form_id', $application->id)
                    ->whereNull('organization_id')->update(['organization_id' => $application->organization_id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep organization ownership and memberships when rolling back application code.
    }
};
