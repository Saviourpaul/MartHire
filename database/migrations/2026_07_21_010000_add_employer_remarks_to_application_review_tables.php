<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addEmployerRemarksColumn('application_forms');
        $this->addEmployerRemarksColumn('application_documents');
    }

    public function down(): void
    {
        $this->dropEmployerRemarksColumn('application_documents');
        $this->dropEmployerRemarksColumn('application_forms');
    }

    private function addEmployerRemarksColumn(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'employer_remarks')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->text('employer_remarks')->nullable()->after('reviewed_at');
        });
    }

    private function dropEmployerRemarksColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'employer_remarks')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('employer_remarks');
        });
    }
};
