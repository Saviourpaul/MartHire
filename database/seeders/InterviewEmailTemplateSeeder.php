<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterviewEmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
}
