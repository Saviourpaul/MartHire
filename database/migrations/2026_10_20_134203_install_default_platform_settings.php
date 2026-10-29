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
        DB::table('platform_settings')->insertOrIgnore([
            'id' => 1, 'display_name' => 'MartHire', 'public_registration_enabled' => true,
            'version' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('platform_settings')->where('id', 1)->where('version', 1)->whereNull('updated_by')->delete();
    }
};
