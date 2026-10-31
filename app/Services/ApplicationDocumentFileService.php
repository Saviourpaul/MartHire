<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;

class ApplicationDocumentFileService
{
    public function authorize(?User $user, ApplicationDocument $document): void
    {
        abort_unless($this->userCanAccess($user, $document), 403);
    }

    public function userCanAccess(?User $user, ApplicationDocument $document): bool
    {
        if (! $user) {
            return false;
        }

        $document->loadMissing('applicationForm.job');

        $application = $document->applicationForm;

        if (! $application) {
            return false;
        }

        return Gate::forUser($user)->allows('viewDocuments', $application);
    }

    public function diskFor(ApplicationDocument $document): FilesystemAdapter
    {
        $path = trim((string) $document->file_path);

        abort_if($path === '', 404);

        $disk = Storage::disk(config('filesystems.application_documents_disk', 'local'));
        abort_unless($disk->exists($path), 404);

        return $disk;
    }

    public function logView(User $user, ApplicationDocument $document, string $action): void
    {
        app(ActivityLogger::class)->record($document->organization_id, $document->applicationForm, $user, 'document_view', [
            'document_id' => $document->id, 'action' => $action,
        ]);
    }

    /**
     * Describe an already-authorized document without failing the whole workspace
     * when an individual private file has been removed or cannot be read.
     *
     * @return array{available: bool, previewable: bool, mime_type: ?string}
     */
    public function metadataFor(ApplicationDocument $document): array
    {
        $unavailable = ['available' => false, 'previewable' => false, 'mime_type' => null];
        $path = trim((string) $document->file_path);
        if ($path === '') {
            return $unavailable;
        }
        $disk = Storage::disk(config('filesystems.application_documents_disk', 'local'));
        try {
            if (! $disk->exists($path)) {
                return $unavailable;
            }
            $mimeType = $this->mimeTypeFor($document, $disk);
            if ($mimeType === null) {
                return $unavailable;
            }

            return ['available' => true, 'previewable' => ApplicationDocument::mimeTypeCanPreview($mimeType), 'mime_type' => $mimeType];
        } catch (FilesystemException) {
            return $unavailable;
        }
    }

    public function assertPreviewable(ApplicationDocument $document, FilesystemAdapter $disk): void
    {
        abort_unless(
            ApplicationDocument::mimeTypeCanPreview($this->mimeTypeFor($document, $disk)),
            415,
            'This document type cannot be previewed. Download it instead.'
        );
    }

    /**
     * @return array<string, string>
     */
    public function responseHeaders(ApplicationDocument $document, FilesystemAdapter $disk): array
    {
        return [
            'Content-Type' => $this->mimeTypeFor($document, $disk) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ];
    }

    private function mimeTypeFor(ApplicationDocument $document, FilesystemAdapter $disk): ?string
    {
        // MIME detectors may fall back to a filename extension. Confirm the
        // stored bytes before allowing one of the browser's inline formats.
        $stream = $disk->readStream($document->file_path);
        if (! is_resource($stream)) {
            return null;
        }
        try {
            $signature = fread($stream, 8);
        } finally {
            fclose($stream);
        }
        if ($signature === false) {
            return null;
        }
        if (str_starts_with($signature, '%PDF-')) {
            return 'application/pdf';
        }
        if (str_starts_with($signature, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($signature, "\xff\xd8\xff")) {
            return 'image/jpeg';
        }
        $mimeType = $disk->mimeType($document->file_path);
        $mimeType = $mimeType ? strtolower(trim(explode(';', $mimeType, 2)[0])) : null;

        return ApplicationDocument::mimeTypeCanPreview($mimeType) ? 'application/octet-stream' : ($mimeType ?: 'application/octet-stream');
    }
}
