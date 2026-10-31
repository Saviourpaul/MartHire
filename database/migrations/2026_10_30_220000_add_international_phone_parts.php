<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'application_forms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                // Legacy values stay untouched; split parts are filled on a validated save.
                $table->string('phone_country', 2)->nullable();
                $table->string('phone_dial_code', 8)->nullable();
                $table->string('phone_national_number', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'application_forms'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['phone_country', 'phone_dial_code', 'phone_national_number']));
        }
    }
};
