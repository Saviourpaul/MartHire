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
        DB::table('security_challenges')->where('purpose', 'platform_settings_update')
            ->whereNull('consumed_at')->update(['consumed_at' => now(), 'code_hash' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Consumed confirmation codes must never be restored.
    }
};
