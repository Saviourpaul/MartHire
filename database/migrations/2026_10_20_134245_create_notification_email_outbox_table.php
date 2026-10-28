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
        Schema::create('notification_email_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->string('channel', 20)->default('mail');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at');
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error', 120)->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'channel']);
            $table->index(['status', 'available_at', 'locked_until'], 'notification_outbox_dispatch_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_email_outbox');
    }
};
