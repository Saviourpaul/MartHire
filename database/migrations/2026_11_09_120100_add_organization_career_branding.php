<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique();
        });

        DB::table('organizations')->orderBy('id')->chunkById(500, function ($organizations): void {
            foreach ($organizations as $organization) {
                $base = mb_substr(Str::slug($organization->name) ?: 'organization', 0, 220);
                $slug = $base.'-'.$organization->id;
                DB::table('organizations')->where('id', $organization->id)->update(['slug' => $slug]);
            }
        });

        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->string('industry', 100)->nullable();
            $table->string('organization_size', 24)->nullable();
            $table->char('brand_primary_color', 7)->nullable();
            $table->char('brand_accent_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->dropColumn(['industry', 'organization_size', 'brand_primary_color', 'brand_accent_color']);
        });
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
