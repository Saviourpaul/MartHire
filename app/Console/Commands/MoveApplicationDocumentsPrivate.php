<?php

namespace App\Console\Commands;

use App\Models\ApplicationDocument;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MoveApplicationDocumentsPrivate extends Command
{
    protected $signature = 'app:move-application-documents-private
        {--commit : Move files instead of running a dry run}
        {--delete-public : Delete the public copy after a verified private copy exists}
        {--include-unreferenced : Also scan public application-documents and user-identification-documents folders}
        {--force : Skip confirmation prompts}
        {--limit= : Limit the number of documents to scan}';

    protected $description = 'Move existing public application documents into private local storage.';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $deletePublic = (bool) $this->option('delete-public');
        $limit = $this->limit();

        if ($deletePublic && ! $commit) {
            $this->warn('The --delete-public option is ignored during a dry run.');
            $deletePublic = false;
        }

        if ($deletePublic && ! $this->option('force')) {
            $confirmed = $this->confirm(
                'This will delete public copies after private copies are verified. Continue?',
                false
            );

            if (! $confirmed) {
                $this->warn('Cancelled.');

                return self::FAILURE;
            }
        }

        $this->components->info($commit
            ? 'Moving application documents into private storage...'
            : 'Dry run: scanning public application documents...');

        $stats = [
            'scanned' => 0,
            'public_copies' => 0,
            'would_move' => 0,
            'moved' => 0,
            'already_private' => 0,
            'public_deleted' => 0,
            'missing_public' => 0,
            'blank_path' => 0,
            'failed' => 0,
        ];

        $query = ApplicationDocument::withoutGlobalScope(OrganizationScope::class)
            ->select(['id', 'file_path'])
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $processedPaths = [];

        foreach ($query->cursor() as $document) {
            $path = str_replace('\\', '/', trim((string) $document->file_path));

            if ($path !== '' && isset($processedPaths[$path])) {
                continue;
            }

            $processedPaths[$path] = true;
            $stats['scanned']++;

            $this->moveFile($path, "document {$document->id}", $commit, $deletePublic, $stats);
        }

        if ($this->option('include-unreferenced')) {
            foreach (['application-documents', 'user-identification-documents'] as $directory) {
                foreach (Storage::disk('public')->allFiles($directory) as $path) {
                    $path = str_replace('\\', '/', trim($path));

                    if (isset($processedPaths[$path])) {
                        continue;
                    }

                    if ($limit !== null && $stats['scanned'] >= $limit) {
                        break 2;
                    }

                    $processedPaths[$path] = true;
                    $stats['scanned']++;
                    $this->moveFile($path, 'unreferenced file', $commit, $deletePublic, $stats);
                }
            }
        }

        $this->table(
            ['Metric', 'Count'],
            collect($stats)
                ->map(fn (int $count, string $metric): array => [str_replace('_', ' ', $metric), $count])
                ->values()
                ->all()
        );

        if (! $commit) {
            $this->line('Run with --commit to copy missing private files.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function moveFile(string $path, string $label, bool $commit, bool $deletePublic, array &$stats): void
    {
        if ($path === '') {
            $stats['blank_path']++;

            return;
        }

        $normalizedPath = str_replace('\\', '/', $path);

        if (str_starts_with($normalizedPath, '/') || str_contains($normalizedPath, ':')
            || preg_match('#(^|/)\.\.(/|$)#', $normalizedPath)
            || preg_match('#[ .]+(/|$)#', $normalizedPath)
            || preg_match('/[\x00-\x1F\x7F]/', $normalizedPath)) {
            $stats['failed']++;
            $this->error("Unsafe file path for {$label}.");

            return;
        }

        $path = $normalizedPath;

        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');

        $isPrivate = $localDisk->exists($path);
        $isPublic = $publicDisk->exists($path);

        if ($isPublic) {
            $stats['public_copies']++;
        }

        if ($isPrivate) {
            $stats['already_private']++;

            if ($commit && $deletePublic && $isPublic) {
                if (! $this->privateCopyMatchesPublic($path)) {
                    $stats['failed']++;
                    $this->error("Existing private copy verification failed for {$label}: {$path}");

                    return;
                }

                if (! $publicDisk->delete($path)) {
                    $stats['failed']++;
                    $this->error("Failed to delete public copy for {$label}: {$path}");

                    return;
                }

                $stats['public_deleted']++;
            }

            return;
        }

        if (! $isPublic) {
            $stats['missing_public']++;
            $this->warn("Missing public file for {$label}: {$path}");

            return;
        }

        if (! $commit) {
            $stats['would_move']++;

            return;
        }

        try {
            $publicContents = $publicDisk->get($path);

            $localDisk->put($path, $publicContents);

            if (! $this->privateCopyMatchesPublic($path)) {
                $stats['failed']++;
                $this->error("Private copy verification failed for {$label}: {$path}");

                return;
            }

            $stats['moved']++;

            if ($deletePublic) {
                if (! $publicDisk->delete($path)) {
                    $stats['failed']++;
                    $this->error("Failed to delete public copy for {$label}: {$path}");

                    return;
                }

                $stats['public_deleted']++;
            }
        } catch (Throwable $throwable) {
            $stats['failed']++;
            $this->error("Failed to move {$label}: {$throwable->getMessage()}");
        }
    }

    private function privateCopyMatchesPublic(string $path): bool
    {
        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $privateStream = null;
        $publicStream = null;

        try {
            if (! $localDisk->exists($path) || ! $publicDisk->exists($path)
                || $localDisk->size($path) !== $publicDisk->size($path)) {
                return false;
            }

            $privateStream = $localDisk->readStream($path);
            $publicStream = $publicDisk->readStream($path);

            if (! is_resource($privateStream) || ! is_resource($publicStream)) {
                return false;
            }

            $privateHash = hash_init('sha256');
            $publicHash = hash_init('sha256');
            $privateBytes = hash_update_stream($privateHash, $privateStream);
            $publicBytes = hash_update_stream($publicHash, $publicStream);

            return $privateBytes === $localDisk->size($path)
                && $publicBytes === $publicDisk->size($path)
                && hash_equals(hash_final($privateHash), hash_final($publicHash));
        } catch (Throwable) {
            return false;
        } finally {
            if (is_resource($privateStream)) {
                fclose($privateStream);
            }

            if (is_resource($publicStream)) {
                fclose($publicStream);
            }
        }
    }

    private function limit(): ?int
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return null;
        }

        if (! is_numeric($limit) || (int) $limit < 1) {
            $this->fail('The --limit option must be a positive integer.');
        }

        return (int) $limit;
    }
}
