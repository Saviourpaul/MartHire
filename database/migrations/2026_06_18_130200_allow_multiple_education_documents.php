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
        if (! Schema::hasTable('application_documents')) {
            return;
        }

        if (! Schema::hasIndex('application_documents', 'application_documents_application_form_id_index')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->index('application_form_id');
            });
        }

        if (Schema::hasIndex('application_documents', 'application_documents_application_form_id_document_type_unique')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->dropUnique('application_documents_application_form_id_document_type_unique');
            });
        }

        if (! Schema::hasIndex('application_documents', 'application_documents_application_form_id_document_type_index')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->index(['application_form_id', 'document_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('application_documents')) {
            return;
        }

        if (! Schema::hasIndex('application_documents', 'application_documents_application_form_id_index')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->index('application_form_id');
            });
        }

        if (Schema::hasIndex('application_documents', 'application_documents_application_form_id_document_type_index')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->dropIndex('application_documents_application_form_id_document_type_index');
            });
        }

        if (! Schema::hasIndex('application_documents', 'application_documents_application_form_id_document_type_unique')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->unique(['application_form_id', 'document_type']);
            });
        }

        if (Schema::hasIndex('application_documents', 'application_documents_application_form_id_document_type_unique')
            && Schema::hasIndex('application_documents', 'application_documents_application_form_id_index')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->dropIndex('application_documents_application_form_id_index');
            });
        }
    }
};
