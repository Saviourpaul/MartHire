<?php

namespace App\Models;

use App\Enums\ApplicationDocumentType;
use Database\Factories\ApplicationDocumentFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

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
        'document_type',
        'document_name',
        'document_number',
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

    /**
     * @return Attribute<?string, ?string>
     */
    protected function documentNumber(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => self::decryptDocumentNumberValue($value),
            set: fn (mixed $value): ?string => self::encryptDocumentNumberValue($value),
        );
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
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

    public function maskedDocumentNumber(): ?string
    {
        $number = $this->document_number;

        if ($number === null || $number === '') {
            return null;
        }

        $length = strlen($number);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($number, -4);
    }

    public function documentNumberIsEncrypted(): bool
    {
        return self::documentNumberValueIsEncrypted($this->getRawOriginal('document_number'));
    }

    public static function decryptDocumentNumberValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public static function encryptDocumentNumberValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        if ($value === '') {
            return null;
        }

        if (self::documentNumberValueIsEncrypted($value)) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    public static function documentNumberValueIsEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
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
