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
            $settings = DB::table('platform_settings')->where('id', 1)->lockForUpdate()->first();
            if (! $settings) {
                return;
            }
            $values = [
                'site_logo_path' => $settings->site_logo_path ?: ($settings->logo_light_path ?: $settings->logo_dark_path),
                'logo_light_path' => null, 'logo_dark_path' => null,
            ];
            foreach ([
                'facebook_url' => 'https://www.facebook.com/MartHirerecruit',
                'x_url' => 'https://x.com/MartHirerecruit',
                'instagram_url' => 'https://www.instagram.com/marthirerecruit',
                'linkedin_url' => 'https://www.linkedin.com/company/marthire/',
            ] as $column => $url) {
                if (blank($settings->{$column})) {
                    $values[$column] = $url;
                }
            }
            DB::table('platform_settings')->where('id', 1)->update($values);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('platform_settings')->whereNull('logo_light_path')->update(['logo_light_path' => DB::raw('site_logo_path')]);
    }
};
