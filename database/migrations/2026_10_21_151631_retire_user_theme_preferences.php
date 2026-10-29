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
        DB::table('user_preferences')->where('theme', '!=', 'light')->update(['theme' => 'light']);
    }

    /**
     * Retired theme choices cannot be recovered when rolling back.
     */
    public function down(): void {}
};
