<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The original install_system_interview_email_templates migration read its
 * rows from config('interviews.defaults'), but config/interviews.php didn't
 * exist yet when it ran, so it silently inserted nothing. Now that the config
 * exists, seed the same rows here (insertOrIgnore keeps this safe to re-run).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (config('interviews.defaults', []) as $type => $template) {
            DB::table('email_templates')->insertOrIgnore([
                'organization_id' => null, 'type' => $type, 'name' => $template['name'],
                'subject' => $template['subject'], 'body' => $template['body'],
                'is_active' => true, 'is_system' => true, 'system_key' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereNull('organization_id')->where('is_system', true)
            ->whereIn('system_key', array_keys(config('interviews.defaults', [])))->delete();
    }
};
