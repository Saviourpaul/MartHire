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
        Schema::create('candidate_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('interview_id')->constrained()->restrictOnDelete();
            $table->foreignId('application_form_id')->constrained()->restrictOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->unsignedInteger('interview_version');
            $table->string('to_email');
            $table->string('from_email');
            $table->string('from_name');
            $table->string('reply_to_email');
            $table->string('reply_to_name');
            $table->string('rendered_subject');
            $table->longText('rendered_body');
            $table->longText('rendered_html');
            $table->text('confirm_url')->nullable();
            $table->text('decline_url')->nullable();
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_message')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'application_form_id', 'created_at'], 'candidate_messages_history_index');
            $table->index(['interview_id', 'interview_version', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_messages');
    }
};
