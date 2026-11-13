<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_onboarding_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_member_id')->unique()->constrained('organization_members')->cascadeOnDelete();
            $table->string('current_step', 32)->default('welcome');
            $table->string('recruitment_focus', 40)->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('skipped_steps')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('organization_members')->orderBy('id')->chunkById(500, function ($members) use ($now): void {
            DB::table('organization_onboarding_progress')->insertOrIgnore(
                $members->map(fn ($member): array => [
                    'organization_member_id' => $member->id,
                    'current_step' => 'complete',
                    'completed_steps' => json_encode(['welcome', 'profile', 'team', 'finish']),
                    'skipped_steps' => json_encode([]),
                    'completed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_onboarding_progress');
    }
};
