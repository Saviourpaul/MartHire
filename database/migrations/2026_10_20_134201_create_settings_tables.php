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
        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 10)->default('system');
            $table->string('timezone', 100)->nullable();
            $table->timestamps();
        });
        Schema::create('organization_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_interview_timezone', 100)->nullable();
            $table->string('default_interview_contact_name', 120)->nullable();
            $table->string('default_interview_contact_phone', 50)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('display_name', 120)->default('Marthire');
            $table->string('support_email', 254)->nullable();
            $table->string('support_url', 2048)->nullable();
            $table->string('privacy_url', 2048)->nullable();
            $table->string('terms_url', 2048)->nullable();
            $table->string('logo_light_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('icon_path')->nullable();
            $table->boolean('public_registration_enabled')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('settings_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope', 20);
            $table->string('action', 80);
            $table->json('changes');
            $table->timestamp('created_at');
            $table->index(['scope', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_audit_events');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('organization_settings');
        Schema::dropIfExists('user_preferences');
    }
};
