<?php

namespace App\Services;

use App\Jobs\PurgeDeletedAccountFiles;
use App\Models\AccountDeletionLifecycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountDataPurgeService
{
    /**
     * Anonymize recruitment history and permanently delete the account inside
     * the caller's transaction. Storage deletion is deferred until commit.
     */
    public function permanentlyDeleteWithinTransaction(
        User $user,
        ?User $actor,
        string $reason = 'admin_permanent',
    ): AccountDeletionLifecycle {
        if (DB::table('job_posts')->where('employer_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => 'This account owns job postings. Transfer or resolve those postings before permanently deleting the account.',
            ]);
        }

        $paths = $this->collectPersonalFilePaths($user);
        $applicationIds = DB::table('application_forms')->where('user_id', $user->id)->pluck('id');

        if ($applicationIds->isNotEmpty()) {
            $this->redactCandidateMessages($applicationIds->all());
            $this->anonymizeApplicationDocuments($applicationIds->all());
            $this->anonymizeApplications($applicationIds->all());
        }

        $this->anonymizeDemoRequest($user);
        $this->redactAccountAuditPayloads($user);
        $this->anonymizeTeamInvitations($user);

        // Notifications use a polymorphic key without a foreign key, so remove
        // them explicitly before the user row is deleted.
        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->delete();

        $lifecycle = AccountDeletionLifecycle::query()
            ->where('active_user_id', $user->id)
            ->where('status', 'scheduled')
            ->lockForUpdate()
            ->first();

        if (! $lifecycle) {
            $lifecycle = new AccountDeletionLifecycle([
                'user_id' => $user->id,
                'role_snapshot' => $user->role?->value,
                'last_login_at_snapshot' => $user->last_login_at,
                'scheduled_deletion_at' => now(),
                'warning_status' => 'not_required',
            ]);
        }

        $lifecycle->fill([
            'user_id' => $user->id,
            'active_user_id' => null,
            'initiated_by_user_id' => $actor?->id,
            'role_snapshot' => $user->role?->value,
            'reason' => $reason,
            'status' => 'completed',
            'last_login_at_snapshot' => $user->last_login_at,
            'completed_at' => now(),
            'purge_status' => $paths === [] ? 'complete' : 'pending',
            'purge_attempts' => 0,
            'purge_error' => null,
            'purge_paths' => $paths === [] ? null : $paths,
        ])->save();

        if (! $lifecycle->warning_sent_at) {
            $lifecycle->forceFill(['warning_status' => 'not_required'])->save();
        }

        $lifecycleId = (int) $lifecycle->id;
        DB::afterCommit(function () use ($lifecycleId): void {
            try {
                PurgeDeletedAccountFiles::dispatch($lifecycleId);
            } catch (Throwable $exception) {
                Log::warning('Personal account files could not be fully purged.', [
                    'lifecycle_id' => $lifecycleId,
                    'exception' => $exception::class,
                ]);
            }
        });

        $user->forceDelete();

        return $lifecycle;
    }

    public function processPendingFilePurges(int $limit = 100): int
    {
        $ids = AccountDeletionLifecycle::query()
            ->where('status', 'completed')
            ->where(function ($query): void {
                $query->whereIn('purge_status', ['pending', 'failed'])
                    ->orWhere(function ($query): void {
                        $query->where('purge_status', 'processing')
                            ->where('updated_at', '<=', now()->subHour());
                    });
            })
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                $this->purgeFiles((int) $id);
            } catch (Throwable $exception) {
                Log::warning('A pending account file purge attempt failed.', [
                    'lifecycle_id' => (int) $id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $ids->count();
    }

    public function purgeFilesForLifecycle(int $lifecycleId): void
    {
        $this->purgeFiles($lifecycleId);
    }

    private function purgeFiles(int $lifecycleId): void
    {
        $work = DB::transaction(function () use ($lifecycleId): ?array {
            $lifecycle = AccountDeletionLifecycle::query()->lockForUpdate()->find($lifecycleId);
            if (! $lifecycle || $lifecycle->status !== 'completed' || $lifecycle->purge_status === 'complete') {
                return null;
            }
            if ($lifecycle->purge_status === 'processing' && $lifecycle->updated_at->greaterThan(now()->subHour())) {
                return null;
            }

            $lifecycle->forceFill([
                'purge_status' => 'processing',
                'purge_attempts' => (int) $lifecycle->purge_attempts + 1,
                'purge_error' => null,
            ])->save();

            return ['paths' => (array) $lifecycle->purge_paths];
        }, 3);

        if (! $work) {
            return;
        }

        try {
            foreach ($work['paths'] as $file) {
                $diskName = (string) ($file['disk'] ?? '');
                $path = (string) ($file['path'] ?? '');
                $prefixes = array_values(array_filter((array) ($file['prefixes'] ?? []), 'is_string'));
                if (! $this->isSafeStoredPath($diskName, $path, $prefixes)) {
                    throw new \RuntimeException('A stored file path did not match an approved account directory.');
                }

                $disk = Storage::disk($diskName);
                if ($disk->exists($path) && ! $disk->delete($path)) {
                    throw new \RuntimeException('The storage disk did not confirm a file deletion.');
                }
            }

            AccountDeletionLifecycle::query()->whereKey($lifecycleId)->where('purge_status', 'processing')->update([
                'purge_status' => 'complete',
                'purge_error' => null,
                'purge_paths' => null,
            ]);
        } catch (Throwable $exception) {
            AccountDeletionLifecycle::query()->whereKey($lifecycleId)->where('purge_status', 'processing')->update([
                'purge_status' => 'failed',
                'purge_error' => 'A personal file could not be removed; another purge attempt is pending.',
            ]);

            throw $exception;
        }
    }

    /** @return list<array{disk: string, path: string, prefixes: list<string>}> */
    private function collectPersonalFilePaths(User $user): array
    {
        $paths = [];
        $this->appendPath($paths, 'public', $user->profile_image_path, ['profile-images/']);

        $profile = DB::table('applicant_profiles')->where('user_id', $user->id)->first(['resume_path']);
        $this->appendPath($paths, 'local', $profile?->resume_path, ['candidate-resumes/']);

        $identity = DB::table('user_identification_documents')->where('user_id', $user->id)->first(['file_path']);
        if ($identity) {
            $privateDisk = (string) config('filesystems.application_documents_disk', 'local');
            $identityPrefix = 'user-identification-documents/'.$user->id.'/';
            $this->appendPath(
                $paths,
                $privateDisk,
                $identity->file_path,
                [$identityPrefix],
            );
            if ($privateDisk !== 'public') {
                // Older private-file migrations can leave a public duplicate.
                $this->appendPath($paths, 'public', $identity->file_path, [$identityPrefix]);
            }
        }

        $applicationIds = DB::table('application_forms')->where('user_id', $user->id)->pluck('id');
        if ($applicationIds->isNotEmpty()) {
            foreach (DB::table('application_forms')->whereIn('id', $applicationIds)->pluck('profile_image_path') as $path) {
                $this->appendPath($paths, 'public', $path, ['profile-images/']);
            }

            $privateDisk = (string) config('filesystems.application_documents_disk', 'local');
            $documents = DB::table('application_documents')->whereIn('application_form_id', $applicationIds)
                ->get(['application_form_id', 'file_path']);
            foreach ($documents as $document) {
                $documentPrefix = 'application-documents/'.$document->application_form_id.'/';
                $this->appendPath(
                    $paths,
                    $privateDisk,
                    $document->file_path,
                    [$documentPrefix],
                );
                if ($privateDisk !== 'public') {
                    $this->appendPath($paths, 'public', $document->file_path, [$documentPrefix]);
                }
            }
        }

        return array_values(collect($paths)->unique(fn (array $file): string => $file['disk'].'|'.$file['path'])->all());
    }

    /** @param list<array{disk: string, path: string, prefixes: list<string>}> $paths */
    private function appendPath(array &$paths, string $disk, ?string $path, array $allowedPrefixes): void
    {
        if (! $path) {
            return;
        }

        $path = str_replace('\\', '/', trim($path));
        if (! $this->isSafeStoredPath($disk, $path, $allowedPrefixes)) {
            // Keep an unresolved reference for a visible failed-purge record;
            // purgeFiles revalidates it and will refuse to delete unsafe paths.
            $paths[] = ['disk' => $disk, 'path' => $path, 'prefixes' => $allowedPrefixes];

            return;
        }

        $paths[] = ['disk' => $disk, 'path' => $path, 'prefixes' => $allowedPrefixes];
    }

    /** @param list<string> $allowedPrefixes */
    private function isSafeStoredPath(string $disk, string $path, array $allowedPrefixes): bool
    {
        if ($disk === '' || $path === '' || str_contains($path, "\0") || str_starts_with($path, '/')
            || str_ends_with($path, '/') || preg_match('/(^|\/)\.\.?($|\/)/', $path)) {
            return false;
        }

        if ($allowedPrefixes === []) {
            return false;
        }

        $privateDisk = (string) config('filesystems.application_documents_disk', 'local');
        foreach ($allowedPrefixes as $prefix) {
            if (! str_starts_with($path, $prefix)) {
                continue;
            }

            if ($prefix === 'profile-images/' && $disk === 'public' && strlen($path) > strlen($prefix)) {
                return true;
            }
            if ($prefix === 'candidate-resumes/' && $disk === 'local' && strlen($path) > strlen($prefix)) {
                return true;
            }
            if (preg_match('#^application-documents/[1-9][0-9]*/$#', $prefix)
                && in_array($disk, array_unique(['public', $privateDisk]), true)
                && strlen($path) > strlen($prefix)) {
                return true;
            }
            if (preg_match('#^user-identification-documents/[1-9][0-9]*/$#', $prefix)
                && in_array($disk, array_unique(['public', $privateDisk]), true)
                && strlen($path) > strlen($prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<int> $applicationIds */
    private function anonymizeApplications(array $applicationIds): void
    {
        // Candidate response URLs are signed and can be used without a login.
        // Bump the persisted interview version so links already in email cannot
        // modify retained application history after the account is removed.
        if (Schema::hasTable('interviews')) {
            DB::table('interviews')->whereIn('application_form_id', $applicationIds)->update([
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);
        }
        if (Schema::hasTable('application_status_histories') && Schema::hasColumn('application_status_histories', 'remarks')) {
            DB::table('application_status_histories')->whereIn('application_form_id', $applicationIds)->update(['remarks' => null]);
        }

        $columns = [
            'user_id' => null,
            'first_name' => 'Deleted',
            'middle_name' => null,
            'last_name' => 'Applicant',
            'email' => 'redacted@deleted.invalid',
            'phone' => 'REDACTED',
            'nationality' => null,
            'date_of_birth' => null,
            'gender' => null,
            'marital_status' => null,
            'state_of_origin' => null,
            'local_government_area' => null,
            'address' => null,
            'country' => null,
            'state' => null,
            'city' => null,
            'profile_image_path' => null,
            'employer_remarks' => null,
            'phone_country' => null,
            'phone_dial_code' => null,
            'phone_national_number' => null,
            'country_code' => null,
        ];

        $columns = array_filter($columns, fn (string $column): bool => Schema::hasColumn('application_forms', $column), ARRAY_FILTER_USE_KEY);
        DB::table('application_forms')->whereIn('id', $applicationIds)->update($columns);
    }

    /** @param list<int> $applicationIds */
    private function anonymizeApplicationDocuments(array $applicationIds): void
    {
        // Keep document records and their review history, but remove identifying
        // names and replace the path with a non-existent placeholder. The
        // original files were collected for deletion before this update.
        DB::table('application_documents')->whereIn('application_form_id', $applicationIds)->update([
            'user_identification_document_id' => null,
            'document_name' => 'Document removed after account deletion',
            'document_number' => null,
            'file_path' => 'redacted',
            'original_name' => 'Removed',
            'mime_type' => null,
            'size' => null,
            'employer_remarks' => null,
            'updated_at' => now(),
        ]);
    }

    /** @param list<int> $applicationIds */
    private function redactCandidateMessages(array $applicationIds): void
    {
        if (! Schema::hasTable('candidate_messages')) {
            return;
        }

        DB::table('candidate_messages')->whereIn('application_form_id', $applicationIds)->update([
            'to_email' => 'redacted@deleted.invalid',
            'reply_to_email' => 'redacted@deleted.invalid',
            'rendered_subject' => 'Message content removed after account deletion',
            'rendered_body' => 'Message content removed after account deletion.',
            'rendered_html' => '<p>Message content removed after account deletion.</p>',
            'confirm_url' => null,
            'decline_url' => null,
            'failed_at' => DB::raw('CASE WHEN status = \'queued\' THEN CURRENT_TIMESTAMP ELSE failed_at END'),
            'error_message' => 'Message content removed after account deletion.',
            'status' => DB::raw("CASE WHEN status = 'queued' THEN 'failed' ELSE status END"),
            'updated_at' => now(),
        ]);
    }

    private function anonymizeDemoRequest(User $user): void
    {
        if (! Schema::hasTable('organization_demo_requests')) {
            return;
        }

        $requestIds = DB::table('organization_demo_requests')->where('converted_user_id', $user->id)->pluck('id');
        if ($requestIds->isEmpty()) {
            return;
        }

        $replacement = [
            'contact_first_name' => 'Deleted',
            'contact_last_name' => 'Account',
            'business_email' => 'redacted+'.$user->id.'@deleted.invalid',
            'active_email_hash' => null,
            'email_domain_hash' => null,
            'phone' => 'REDACTED',
            'phone_country' => 'ZZ',
            'phone_dial_code' => '0',
            'phone_national_number' => '0',
            'risk_flags' => null,
            'internal_notes' => null,
            'rejection_reason' => null,
            'setup_token_hash' => null,
            'requester_update_token_hash' => null,
            'requester_information_response' => null,
        ];
        $replacement = array_filter($replacement, fn (string $column): bool => Schema::hasColumn('organization_demo_requests', $column), ARRAY_FILTER_USE_KEY);
        DB::table('organization_demo_requests')->whereIn('id', $requestIds)->update($replacement);

        if (Schema::hasTable('organization_demo_request_events')) {
            DB::table('organization_demo_request_events')->whereIn('organization_demo_request_id', $requestIds)
                ->update(['details' => null]);
        }
    }

    private function redactAccountAuditPayloads(User $user): void
    {
        DB::table('settings_audit_events')->where('user_id', $user->id)->update(['changes' => json_encode([])]);

        DB::table('activity_logs')->where('actor_id', $user->id)->orderBy('id')->chunkById(250, function ($logs): void {
            foreach ($logs as $log) {
                $payload = json_decode((string) $log->payload, true);
                if (! is_array($payload)) {
                    $payload = [];
                }
                $payload['actor_name'] = 'Deleted account';

                DB::table('activity_logs')->where('id', $log->id)->update([
                    'actor_id' => null,
                    'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    private function anonymizeTeamInvitations(User $user): void
    {
        if (! Schema::hasTable('team_invitations')) {
            return;
        }

        DB::table('team_invitations')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)])
            ->update([
                'email' => 'redacted+'.$user->id.'@deleted.invalid',
                'status' => DB::raw("CASE WHEN status = 'pending' THEN 'revoked' ELSE status END"),
                'updated_at' => now(),
            ]);
    }
}
