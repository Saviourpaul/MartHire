<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_posts') && ! Schema::hasColumn('job_posts', 'requires_identity_document')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->boolean('requires_identity_document')->default(false);
            });
        }

        if (Schema::hasTable('job_posts') && ! Schema::hasColumn('job_posts', 'identity_requirement_reason')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->string('identity_requirement_reason', 255)->nullable();
            });
        }

        foreach (['user_identification_documents', 'application_documents'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'issuing_country_code')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->char('issuing_country_code', 2)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['user_identification_documents', 'application_documents'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'issuing_country_code')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('issuing_country_code');
                });
            }
        }

        if (Schema::hasTable('job_posts')) {
            $columns = array_values(array_filter(
                ['requires_identity_document', 'identity_requirement_reason'],
                fn (string $column): bool => Schema::hasColumn('job_posts', $column),
            ));

            if ($columns !== []) {
                Schema::table('job_posts', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
