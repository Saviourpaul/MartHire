<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $socialColumns = ['facebook_url', 'x_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url'];

        foreach ($socialColumns as $column) {
            if (Schema::hasColumn('platform_settings', $column) && Schema::getColumnType('platform_settings', $column) !== 'text') {
                Schema::table('platform_settings', function (Blueprint $table) use ($column): void {
                    $table->text($column)->nullable()->change();
                });
            }
        }

        foreach (['site_logo_path' => 255, 'support_phone' => 50, 'support_address' => 1000] as $column => $length) {
            if (! Schema::hasColumn('platform_settings', $column)) {
                Schema::table('platform_settings', function (Blueprint $table) use ($column, $length): void {
                    $table->string($column, $length)->nullable();
                });
            }
        }

        foreach ($socialColumns as $column) {
            if (! Schema::hasColumn('platform_settings', $column)) {
                Schema::table('platform_settings', function (Blueprint $table) use ($column): void {
                    $table->text($column)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(['site_logo_path', 'support_phone', 'support_address', 'facebook_url', 'x_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url'],
            fn (string $column): bool => Schema::hasColumn('platform_settings', $column)));
        if ($columns !== []) {
            Schema::table('platform_settings', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
