<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->longText('body')->change();
        });

        Schema::table('candidate_messages', function (Blueprint $table): void {
            $table->string('from_name', 512)->change();
            $table->string('reply_to_name', 512)->change();
        });
    }

    /**
     * Retain wider columns on rollback to avoid truncating saved email snapshots.
     */
    public function down(): void {}
};
