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
            DB::table('job_posts')->whereIn('status', ['approved', 'pending', 'rejected', 'active', 'inactive'])
                ->whereNotNull('suspended_at')->update(['status' => 'suspended']);
            DB::table('job_posts')->where('status', 'rejected')->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);
            DB::table('job_posts')->where('status', 'suspended')->whereNull('suspension_reason')->update([
                'suspension_reason' => 'Previously restricted. Update the job to meet platform requirements before reopening.',
            ]);
            DB::table('job_posts')->whereIn('status', ['approved', 'pending', 'active'])->update(['status' => 'open']);
            DB::table('job_posts')->where('status', 'inactive')->update(['status' => 'closed']);
            DB::table('job_posts')->where('status', 'open')->where('due_date', '<', today()->toDateString())
                ->update(['status' => 'closed']);
        });
    }

    /**
     * Restore legacy-compatible values. Previous pending/approved distinctions cannot be reconstructed.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('job_posts')->where('status', 'open')->update(['status' => 'approved']);
            DB::table('job_posts')->whereIn('status', ['suspended', 'closed'])->update(['status' => 'rejected']);
        });
    }
};
