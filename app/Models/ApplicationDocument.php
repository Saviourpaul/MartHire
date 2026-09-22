<?php

namespace App\Models;

use App\Enums\ApplicationDocumentType;
use Database\Factories\ApplicationDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    /** @use HasFactory<ApplicationDocumentFactory> */
    use HasFactory;

    public const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    protected $fillable = [
        'application_form_id',
        'user_identification_document_id',
        'document_type',
        'document_name',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => ApplicationDocumentType::class,
        ];
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    public function identificationDocument(): BelongsTo
    {
        return $this->belongsTo(UserIdentificationDocument::class, 'user_identification_document_id');
    }

    public function downloadUrl(): string
    {
        return route('application-documents.download', $this);
    }

    public function previewUrl(): string
    {
        return route('application-documents.preview', $this);
    }

    public function canPreviewInline(): bool
    {
        return self::mimeTypeCanPreview($this->mime_type);
    }

    public static function mimeTypeCanPreview(?string $mimeType): bool
    {
        if (! $mimeType) {
            return false;
        }

        $mimeType = strtolower(trim(explode(';', $mimeType, 2)[0]));

        return in_array($mimeType, self::PREVIEWABLE_MIME_TYPES, true);
    }
}
