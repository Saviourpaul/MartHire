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
        foreach (config('interviews.defaults', []) as $type => $template) {
            DB::table('email_templates')->insertOrIgnore([
                'organization_id' => null, 'type' => $type, 'name' => $template['name'],
                'subject' => $template['subject'], 'body' => $template['body'],
                'is_active' => true, 'is_system' => true, 'system_key' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->whereNull('organization_id')->where('is_system', true)
            ->whereIn('system_key', array_keys(config('interviews.defaults', [])))->delete();
    }
};
