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
        Schema::create('organization_member_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('new_application_email')->nullable();
            $table->boolean('interview_response_email')->nullable();
            $table->boolean('candidate_email_failure_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_member_preferences');
    }
};
