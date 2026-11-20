<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class ApplicationUploadLimits
{
    private const MAX_FORM_FILE_BYTES = 65 * 1024 * 1024;

    private const MAX_SINGLE_FILE_BYTES = 5 * 1024 * 1024;

    private const MAX_MULTIPART_RESERVE_BYTES = 1024 * 1024;

    private const MIN_MULTIPART_RESERVE_BYTES = 16 * 1024;

    public static function maxTotalFileBytes(): int
    {
        return self::maxTotalFileBytesFor(trim((string) ini_get('post_max_size')));
    }

    public static function maxTotalFileBytesFor(string $postMaxSize): int
    {
        $postMaxSize = trim($postMaxSize);
        if ($postMaxSize === '' || $postMaxSize === '0') {
            return self::MAX_FORM_FILE_BYTES;
        }

        $requestLimit = self::parseIniSize($postMaxSize);

        if ($requestLimit <= 0) {
            return 0;
        }

        $multipartReserve = min(
            self::MAX_MULTIPART_RESERVE_BYTES,
            max(self::MIN_MULTIPART_RESERVE_BYTES, (int) floor($requestLimit * 0.05))
        );

        return min(
            self::MAX_FORM_FILE_BYTES,
            max(0, $requestLimit - $multipartReserve)
        );
    }

    public static function requestBodyLimitBytes(): ?int
    {
        $postMaxSize = trim((string) ini_get('post_max_size'));

        if ($postMaxSize === '' || $postMaxSize === '0') {
            return null;
        }

        $requestLimit = self::parseIniSize($postMaxSize);

        return $requestLimit > 0 ? $requestLimit : 0;
    }

    public static function maxFileCount(): int
    {
        $maxFileUploads = trim((string) ini_get('max_file_uploads'));

        return $maxFileUploads === '' ? 20 : max(0, (int) $maxFileUploads);
    }

    public static function maxSingleFileBytes(): int
    {
        return self::maxSingleFileBytesFor(trim((string) ini_get('upload_max_filesize')));
    }

    public static function maxSingleFileBytesFor(string $uploadMaxFilesize): int
    {
        $uploadMaxFilesize = trim($uploadMaxFilesize);
        if ($uploadMaxFilesize === '' || $uploadMaxFilesize === '0') {
            return self::MAX_SINGLE_FILE_BYTES;
        }

        $uploadLimit = self::parseIniSize($uploadMaxFilesize);

        return $uploadLimit > 0 ? min(self::MAX_SINGLE_FILE_BYTES, $uploadLimit) : 0;
    }

    /**
     * @return array{
     *     file_count?: array{actual: int, limit: int},
     *     total_bytes?: array{actual: int, limit: int}
     * }
     */
    public static function violationsForFiles(iterable $files): array
    {
        $uploads = collect($files)
            ->flatten()
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->values();
        $violations = [];
        $maxFiles = self::maxFileCount();

        if ($uploads->count() > $maxFiles) {
            $violations['file_count'] = ['actual' => $uploads->count(), 'limit' => $maxFiles];
        }

        $totalBytes = $uploads->sum(fn (UploadedFile $file): int => (int) ($file->getSize() ?: 0));
        $maxTotalBytes = self::maxTotalFileBytes();

        if ($totalBytes > $maxTotalBytes) {
            $violations['total_bytes'] = ['actual' => $totalBytes, 'limit' => $maxTotalBytes];
        }

        return $violations;
    }

    /** @param array{file_count?: array{actual: int, limit: int}, total_bytes?: array{actual: int, limit: int}} $violations
     * @return list<string>
     */
    public static function messagesForUploadViolations(array $violations): array
    {
        $messages = [];

        if (isset($violations['file_count'])) {
            $messages[] = __('You selected :actual files. This form can send up to :limit files at once. Remove a file and try again.', $violations['file_count']);
        }

        if (isset($violations['total_bytes'])) {
            $messages[] = __('Your selected files total :selected MB. This form can send up to :limit MB at once. Remove a file or choose smaller files, then try again.', [
                'selected' => number_format($violations['total_bytes']['actual'] / 1024 / 1024, 1),
                'limit' => number_format($violations['total_bytes']['limit'] / 1024 / 1024, 1),
            ]);
        }

        return $messages;
    }

    private static function parseIniSize(string $value): int
    {
        if (! preg_match('/^\s*(\d+(?:\.\d+)?)\s*([kmg]?)\s*$/i', $value, $matches)) {
            return 0;
        }

        $power = match (strtolower($matches[2])) {
            'g' => 3,
            'm' => 2,
            'k' => 1,
            default => 0,
        };

        return (int) ((float) $matches[1] * (1024 ** $power));
    }
}
