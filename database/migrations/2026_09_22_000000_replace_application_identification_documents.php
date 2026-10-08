<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('application_documents')) {
            Schema::table('application_documents', function (Blueprint $table): void {
                $table->string('document_type', 50)->change();
            });

            DB::table('application_documents')
                ->where('document_type', 'nin')
                ->update([
                    'document_type' => 'national_identity_card',
                    'document_name' => 'National Identity Card / NIN Slip',
                ]);

            DB::table('application_documents')
                ->where('document_type', 'bvn')
                ->update([
                    'document_type' => 'legacy_identity',
                    'document_name' => 'Legacy identity document',
                ]);
        }

        if (! Schema::hasTable('user_identification_documents')) {
            Schema::create('user_identification_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();

                $table->index('document_type');
            });
        }

        if (Schema::hasTable('application_documents')
            && ! Schema::hasColumn('application_documents', 'user_identification_document_id')) {
            Schema::table('application_documents', function (Blueprint $table): void {
                $table->foreignId('user_identification_document_id')
                    ->nullable()
                    ->after('application_form_id')
                    ->constrained('user_identification_documents')
                    ->nullOnDelete();
            });
        }

        $this->associateExistingNationalIdentityDocuments();
    }

    public function down(): void
    {
        // This is a forward-only data migration. Existing documents are intentionally
        // retained so rolling back does not discard applicant identification evidence.
    }

    private function associateExistingNationalIdentityDocuments(): void
    {
        if (! Schema::hasTable('application_documents')
            || ! Schema::hasTable('application_forms')
            || ! Schema::hasTable('user_identification_documents')) {
            return;
        }

        $documents = DB::table('application_documents')
            ->join('application_forms', 'application_forms.id', '=', 'application_documents.application_form_id')
            ->where('application_documents.document_type', 'national_identity_card')
            ->orderByDesc('application_forms.submitted_at')
            ->orderByDesc('application_documents.id')
            ->select([
                'application_forms.user_id',
                'application_documents.file_path',
                'application_documents.original_name',
                'application_documents.mime_type',
                'application_documents.size',
                'application_documents.created_at',
                'application_documents.updated_at',
            ]);

        foreach ($documents->cursor() as $document) {
            DB::table('user_identification_documents')->insertOrIgnore([
                'user_id' => $document->user_id,
                'document_type' => 'national_identity_card',
                'file_path' => $document->file_path,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'created_at' => $document->created_at ?? now(),
                'updated_at' => $document->updated_at ?? now(),
            ]);
        }
    }
};
