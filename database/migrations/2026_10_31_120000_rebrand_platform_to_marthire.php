<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->string('display_name', 120)->default('Marthire')->change();
        });

        DB::transaction(function (): void {
            $settings = DB::table('platform_settings')->where('id', 1)->lockForUpdate()->first();
            if (! $settings) {
                return;
            }

            $values = [];
            foreach (['display_name', 'mail_from_name'] as $field) {
                $value = $settings->{$field};
                // Recognize only stock product names. Custom branding, email
                // addresses and verified social URLs retain their owners' values.
                if ($value !== 'Marthire' && in_array(strtolower(trim((string) $value)), ['nearbyworks', 'nearby works', 'marthire', 'laravel'], true)) {
                    $values[$field] = 'Marthire';
                }
            }

            if ($values !== []) {
                DB::table('platform_settings')->where('id', 1)->update([
                    ...$values, 'version' => (int) $settings->version + 1, 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve branding on rollback: later administrator edits cannot be
        // distinguished reliably from this one-time stock-name migration.
    }
};
