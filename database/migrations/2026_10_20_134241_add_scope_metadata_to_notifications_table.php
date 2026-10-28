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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('scope', 20)->default('account');
            $table->string('category', 50)->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 30)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event_key', 191)->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->unique(['notifiable_type', 'notifiable_id', 'event_key'], 'notifications_recipient_event_unique');
            $table->index(['notifiable_id', 'scope', 'organization_id', 'read_at'], 'notifications_scope_unread_index');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropUnique('notifications_recipient_event_unique');
            $table->dropIndex('notifications_scope_unread_index');
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['scope', 'category', 'organization_id', 'subject_type', 'subject_id', 'event_key', 'schema_version']);
        });
    }
};
