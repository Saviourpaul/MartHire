<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addRemarksColumn('application_status_histories');
        $this->addRemarksColumn('application_document_status_histories');
    }

    public function down(): void
    {
        $this->dropRemarksColumn('application_document_status_histories');
        $this->dropRemarksColumn('application_status_histories');
    }

    private function addRemarksColumn(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'remarks')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->text('remarks')->nullable()->after('changed_by');
        });
    }

    private function dropRemarksColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'remarks')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('remarks');
        });
    }
};
