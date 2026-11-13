<?php

namespace App\Services;

use App\Enums\OrganizationDemoOnboardingStatus;
use App\Enums\OrganizationDemoRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationDemoRequest;
use App\Models\OrganizationDemoRequestEvent;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Notifications\OrganizationDemoRequestEmail;
use App\Notifications\OrganizationDemoSetupInvitation;
use App\Support\OrganizationContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrganizationDemoRequestService
{
    public function __construct(
        private readonly OrganizationProvisioner $provisioner,
        private readonly OrganizationContext $context,
        private readonly WorkspaceUpdateBroadcaster $workspaceUpdates,
        private readonly OrganizationDemoRiskAssessment $riskAssessment,
        private readonly WorkflowNotificationService $workflowNotifications,
    ) {}

    public function submit(array $data): OrganizationDemoRequest
    {
        $email = Str::lower(trim($data['business_email']));
        $fingerprint = hash('sha256', $email);

        try {
            [$request, $event, $created] = DB::transaction(function () use ($data, $email, $fingerprint): array {
                $existing = OrganizationDemoRequest::query()
                    ->where('active_email_hash', $fingerprint)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->forceFill([
                        'risk_flags' => array_values(array_unique([...($existing->risk_flags ?? []), 'repeat_submission'])),
                    ])->save();
                    $event = $this->record($existing, null, 'duplicate_submission_detected', ['matched_by' => 'email']);

                    return [$existing->refresh(), $event, false];
                }

                $risk = $this->riskAssessment->assess($email);
                $request = OrganizationDemoRequest::query()->create([
                    'contact_first_name' => trim($data['contact_first_name']),
                    'contact_last_name' => trim($data['contact_last_name']),
                    'company_name' => trim($data['company_name']),
                    'business_email' => $email,
                    'active_email_hash' => $fingerprint,
                    'email_domain_hash' => $risk['email_domain_hash'],
                    'risk_flags' => $risk['risk_flags'],
                    'possible_duplicate_of_id' => $risk['possible_duplicate_of_id'],
                    'phone' => $data['phone'],
                    'phone_country' => $data['phone_country'],
                    'phone_dial_code' => $data['phone_dial_code'],
                    'phone_national_number' => $data['phone_national_number'],
                    'industry' => filled($data['industry'] ?? null) ? trim($data['industry']) : null,
                    'organization_size' => $data['organization_size'],
                    'status' => OrganizationDemoRequestStatus::Submitted,
                    'onboarding_status' => OrganizationDemoOnboardingStatus::NotStarted,
                ]);

                $event = $this->record($request, null, 'submitted', $risk['risk_flags'] === [] ? [] : ['risk_flags' => $risk['risk_flags']]);

                return [$request, $event, true];
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateKey($exception)) {
                $existing = OrganizationDemoRequest::query()->where('active_email_hash', $fingerprint)->first();
                if (! $existing) {
                    throw $exception;
                }

                [$request] = DB::transaction(function () use ($existing): array {
                    $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($existing->id);
                    $locked->forceFill([
                        'risk_flags' => array_values(array_unique([...($locked->risk_flags ?? []), 'repeat_submission'])),
                    ])->save();
                    $event = $this->record($locked, null, 'duplicate_submission_detected', ['matched_by' => 'email']);

                    return [$locked->refresh()];
                });

                $this->retryAdminSubmissionNotification($request);

                return $request;
            }

            throw $exception;
        }

        if ($created || $event->event === 'duplicate_submission_detected') {
            $this->retryAdminSubmissionNotification($request);
        }

        if ($created) {
            $this->sendRequesterEmail($request, $event, 'We received your MarHire demo request', [
                'Thanks for contacting MarHire. Our team will review your request and contact you about the next step.',
            ]);
        }

        return $request;
    }

    public function claim(OrganizationDemoRequest $request, User $admin): OrganizationDemoRequest
    {
        $request = DB::transaction(function () use ($request, $admin): OrganizationDemoRequest {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($this->canReceiveDecision($locked), 409);
            abort_if($locked->assigned_admin_id && $locked->assigned_admin_id !== $admin->id, 409, 'Another administrator owns this review.');

            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::UnderReview,
                'assigned_admin_id' => $admin->id,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
            ])->save();
            $this->record($locked, $admin, 'review_claimed');

            return $locked->refresh();
        });

        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function addNote(OrganizationDemoRequest $request, User $admin, string $note): OrganizationDemoRequest
    {
        $request = DB::transaction(function () use ($request, $admin, $note): OrganizationDemoRequest {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($this->canReceiveDecision($locked), 409);
            $locked->forceFill(['internal_notes' => trim(implode("\n\n", array_filter([
                $locked->internal_notes,
                '['.now()->toDateTimeString().'] '.$admin->name.': '.trim($note),
            ])))]);
            $locked->save();
            $this->record($locked, $admin, 'note_added', ['note' => trim($note)]);

            return $locked->refresh();
        });

        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function offerGuidedDemo(OrganizationDemoRequest $request, User $admin): OrganizationDemoRequest
    {
        $bookingUrl = trim((string) config('organization_demo.booking_url'));
        abort_unless(filter_var($bookingUrl, FILTER_VALIDATE_URL) && parse_url($bookingUrl, PHP_URL_SCHEME) === 'https', 503, 'The guided demo booking link is not configured.');

        [$request, $event] = DB::transaction(function () use ($request, $admin): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless(in_array($locked->status, [
                OrganizationDemoRequestStatus::Submitted,
                OrganizationDemoRequestStatus::UnderReview,
                OrganizationDemoRequestStatus::InformationRequested,
            ], true), 409);
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::GuidedDemoOffered,
                'assigned_admin_id' => $locked->assigned_admin_id ?? $admin->id,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
                'requester_update_token_hash' => null,
                'requester_update_expires_at' => null,
            ])->save();
            $event = $this->record($locked, $admin, 'guided_demo_offered');

            return [$locked->refresh(), $event];
        });

        $this->sendRequesterEmail($request, $event, 'Book a MarHire guided demo', [
            'Our team would be happy to walk you through MarHire. Use the booking link below to choose a convenient time.',
        ], 'Choose a demo time', $bookingUrl);
        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function requestInformation(OrganizationDemoRequest $request, User $admin, string $message): OrganizationDemoRequest
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(max(1, (int) config('organization_demo.requester_update_expire_days', 7)));

        [$request, $event] = DB::transaction(function () use ($request, $admin, $message, $token, $expiresAt): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless(in_array($locked->status, [
                OrganizationDemoRequestStatus::Submitted,
                OrganizationDemoRequestStatus::UnderReview,
                OrganizationDemoRequestStatus::GuidedDemoOffered,
            ], true), 409);
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::InformationRequested,
                'assigned_admin_id' => $locked->assigned_admin_id ?? $admin->id,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
                'requester_update_token_hash' => hash('sha256', $token),
                'requester_update_expires_at' => $expiresAt,
                'requester_information_response' => null,
                'requester_information_received_at' => null,
            ])->save();
            $event = $this->record($locked, $admin, 'information_requested', ['message' => trim($message)]);

            return [$locked->refresh(), $event];
        });

        $url = URL::temporarySignedRoute('organization-demo.followup.show', $expiresAt, ['token' => $token]);
        $this->sendRequesterEmail($request, $event, 'More information is needed for your MarHire request', [
            trim($message),
            'Use the secure link below to send the requested details. The link can be used once and expires in '.max(1, (int) config('organization_demo.requester_update_expire_days', 7)).' days.',
        ], 'Provide more information', $url);
        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function requesterInformation(string $token): OrganizationDemoRequest
    {
        abort_unless(strlen($token) === 64 && ctype_alnum($token), 404);
        $request = OrganizationDemoRequest::query()->where('requester_update_token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($request->status === OrganizationDemoRequestStatus::InformationRequested
            && $request->requester_update_expires_at?->isFuture(), 410);

        return $request;
    }

    public function submitRequesterInformation(string $token, string $response): OrganizationDemoRequest
    {
        $request = $this->requesterInformation($token);
        $request = DB::transaction(function () use ($request, $token, $response): OrganizationDemoRequest {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($locked->status === OrganizationDemoRequestStatus::InformationRequested
                && $locked->requester_update_token_hash === hash('sha256', $token)
                && $locked->requester_update_expires_at?->isFuture(), 410);
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::UnderReview,
                'requester_update_token_hash' => null,
                'requester_update_expires_at' => null,
                'requester_information_response' => trim($response),
                'requester_information_received_at' => now(),
            ])->save();
            $this->record($locked, null, 'requester_information_received', ['response' => trim($response)]);

            return $locked->refresh();
        });

        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function approve(OrganizationDemoRequest $request, User $admin): bool
    {
        [$request, $token] = DB::transaction(function () use ($request, $admin): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($this->canReceiveDecision($locked), 409);
            $this->assertNoExistingAccount($locked->business_email);

            $token = Str::random(64);
            $days = max(1, (int) config('organization_demo.setup_expire_days', 7));
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::Approved,
                'onboarding_status' => OrganizationDemoOnboardingStatus::InvitationSent,
                'assigned_admin_id' => $locked->assigned_admin_id ?? $admin->id,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
                'approved_at' => now(),
                'setup_token_hash' => hash('sha256', $token),
                'setup_expires_at' => now()->addDays($days),
                'setup_sent_at' => now(),
                'setup_signed_at' => now(),
                'requester_update_token_hash' => null,
                'requester_update_expires_at' => null,
                'rejection_reason' => null,
            ])->save();
            $this->record($locked, $admin, 'approved');

            return [$locked->refresh(), $token];
        });

        $sent = $this->sendSetupInvitation($request, $token);
        $this->workspaceUpdates->admins(['demo-requests']);

        return $sent;
    }

    public function resendInvitation(OrganizationDemoRequest $request, User $admin): bool
    {
        [$request, $token] = DB::transaction(function () use ($request, $admin): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($locked->status === OrganizationDemoRequestStatus::Approved, 409);
            abort_if($locked->setup_accepted_at !== null, 409, 'This setup invitation has already been accepted.');
            $this->assertNoExistingAccount($locked->business_email);

            $token = Str::random(64);
            $days = max(1, (int) config('organization_demo.setup_expire_days', 7));
            $locked->forceFill([
                'onboarding_status' => OrganizationDemoOnboardingStatus::InvitationSent,
                'setup_token_hash' => hash('sha256', $token),
                'setup_expires_at' => now()->addDays($days),
                'setup_sent_at' => now(),
                'setup_signed_at' => now(),
            ])->save();
            $this->record($locked, $admin, 'setup_invitation_resent');

            return [$locked->refresh(), $token];
        });

        $sent = $this->sendSetupInvitation($request, $token);
        $this->workspaceUpdates->admins(['demo-requests']);

        return $sent;
    }

    public function reject(OrganizationDemoRequest $request, User $admin, string $reason): OrganizationDemoRequest
    {
        [$request, $event] = DB::transaction(function () use ($request, $admin, $reason): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($this->canReceiveDecision($locked), 409);
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::Rejected,
                'onboarding_status' => OrganizationDemoOnboardingStatus::NotStarted,
                'active_email_hash' => null,
                'setup_token_hash' => null,
                'setup_expires_at' => null,
                'requester_update_token_hash' => null,
                'requester_update_expires_at' => null,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
                'rejected_at' => now(),
                'rejection_reason' => trim($reason),
            ])->save();
            $event = $this->record($locked, $admin, 'rejected', ['reason' => trim($reason)]);

            return [$locked->refresh(), $event];
        });

        $this->sendRequesterEmail($request, $event, 'Update on your MarHire demo request', [
            'Thank you for your interest in MarHire. After reviewing your request, we are unable to proceed at this time.',
            'If you believe this decision needs another look, reply to this email and our team will review your message.',
        ]);
        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function markDuplicate(OrganizationDemoRequest $request, User $admin, ?string $note = null): OrganizationDemoRequest
    {
        $request = DB::transaction(function () use ($request, $admin, $note): OrganizationDemoRequest {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($this->canReceiveDecision($locked), 409);
            $locked->forceFill([
                'status' => OrganizationDemoRequestStatus::Duplicate,
                'active_email_hash' => null,
                'setup_token_hash' => null,
                'setup_expires_at' => null,
                'requester_update_token_hash' => null,
                'requester_update_expires_at' => null,
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => $locked->reviewed_at ?? now(),
            ])->save();
            $this->record($locked, $admin, 'marked_duplicate', ['note' => $note]);

            return $locked->refresh();
        });

        $this->workspaceUpdates->admins(['demo-requests']);

        return $request;
    }

    public function setupInvitation(string $token): OrganizationDemoRequest
    {
        abort_unless(strlen($token) === 64 && ctype_alnum($token), 404);
        $request = OrganizationDemoRequest::query()->where('setup_token_hash', hash('sha256', $token))->firstOrFail();

        if (! $request->setup_expires_at || $request->setup_expires_at->isPast()) {
            DB::transaction(function () use ($request): void {
                $locked = OrganizationDemoRequest::query()->lockForUpdate()->find($request->id);
                if ($locked && $locked->setup_token_hash
                    && (! $locked->setup_expires_at || $locked->setup_expires_at->isPast())) {
                    $locked->onboarding_status = OrganizationDemoOnboardingStatus::InvitationExpired;
                    $locked->save();
                    $this->record($locked, null, 'setup_invitation_expired');
                }
            });

            abort(410, 'This setup invitation has expired. Ask the MarHire team to send a new one.');
        }

        abort_unless($request->status === OrganizationDemoRequestStatus::Approved
            && $request->setup_token_hash !== null && $request->setup_accepted_at === null, 410);

        return $request;
    }

    /** @return array{User, Organization} */
    public function completeSetup(string $token, array $data): array
    {
        $request = $this->setupInvitation($token);
        $result = DB::transaction(function () use ($request, $token, $data): array {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($locked->status === OrganizationDemoRequestStatus::Approved
                && $locked->setup_token_hash === hash('sha256', $token)
                && $locked->setup_expires_at?->isFuture()
                && $locked->setup_accepted_at === null, 410);
            $this->assertNoExistingAccount($locked->business_email);

            $user = User::create([
                'first_name' => trim($data['contact_first_name']),
                'last_name' => trim($data['contact_last_name']),
                'name' => trim($data['contact_first_name'].' '.$data['contact_last_name']),
                'email' => $locked->business_email,
                'password' => $data['password'],
                'role' => UserRole::Organization,
                'status' => UserStatus::Pending,
                'last_login_at' => now(),
            ]);
            $user = app(EmailVerificationService::class)->verifyTrustedEmail($user, $locked->business_email);
            abort_unless($user instanceof User, 403);

            $organization = $this->provisioner->ensureForEmployer($user, $locked->company_name);
            $this->context->runInOrganization($organization, function () use ($organization, $locked): void {
                OrganizationSetting::query()->firstOrCreate(
                    ['organization_id' => $organization->id],
                    ['industry' => $locked->industry, 'organization_size' => $locked->organization_size],
                );
            });

            $locked->forceFill([
                'onboarding_status' => OrganizationDemoOnboardingStatus::InProgress,
                'setup_accepted_at' => now(),
                'setup_token_hash' => null,
                'setup_expires_at' => null,
                'converted_user_id' => $user->id,
                'converted_organization_id' => $organization->id,
            ])->save();
            $this->record($locked, $user, 'account_created', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
            ]);

            return [$user, $organization];
        });

        $this->workspaceUpdates->admins(['demo-requests', 'users', 'organizations']);

        return $result;
    }

    public function markOnboardingCompleted(User $user, Organization $organization): void
    {
        $request = OrganizationDemoRequest::query()
            ->where('converted_user_id', $user->id)
            ->where('converted_organization_id', $organization->id)
            ->where('onboarding_status', OrganizationDemoOnboardingStatus::InProgress)
            ->first();

        if (! $request) {
            return;
        }

        DB::transaction(function () use ($request, $user): void {
            $locked = OrganizationDemoRequest::query()->lockForUpdate()->find($request->id);
            if (! $locked || $locked->onboarding_status !== OrganizationDemoOnboardingStatus::InProgress) {
                return;
            }

            $locked->forceFill([
                'onboarding_status' => OrganizationDemoOnboardingStatus::Completed,
                'completed_at' => now(),
            ])->save();
            $this->record($locked, $user, 'onboarding_completed');
        });

        $this->workspaceUpdates->admins(['demo-requests']);
    }

    private function sendSetupInvitation(OrganizationDemoRequest $request, string $token): bool
    {
        try {
            $days = max(1, (int) config('organization_demo.setup_expire_days', 7));
            $url = URL::temporarySignedRoute(
                'organization-demo.setup.show',
                $request->setup_expires_at,
                ['token' => $token],
            );
            Notification::route('mail', $request->business_email)->notify(new OrganizationDemoSetupInvitation(
                $url,
                trim($request->contact_first_name.' '.$request->contact_last_name),
                $request->company_name,
                $days,
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $request->forceFill(['onboarding_status' => OrganizationDemoOnboardingStatus::InvitationFailed])->save();
            $this->record($request, null, 'setup_invitation_failed');

            return false;
        }
    }

    private function assertNoExistingAccount(string $email): void
    {
        $exists = User::withTrashed()->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'business_email' => 'An account already uses this email. Review the request and resolve the account match before approving.',
            ]);
        }
    }

    private function record(OrganizationDemoRequest $request, ?User $actor, string $event, array $details = []): OrganizationDemoRequestEvent
    {
        return OrganizationDemoRequestEvent::query()->create([
            'organization_demo_request_id' => $request->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'details' => $details === [] ? null : $details,
            'created_at' => now(),
        ]);
    }

    /** @param list<string> $lines */
    private function sendRequesterEmail(
        OrganizationDemoRequest $request,
        OrganizationDemoRequestEvent $event,
        string $subject,
        array $lines,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
    ): void {
        try {
            Notification::route('mail', $request->business_email)->notify(new OrganizationDemoRequestEmail(
                $subject,
                'Hello '.trim($request->contact_first_name.' '.$request->contact_last_name).',',
                $lines,
                $actionLabel,
                $actionUrl,
            ));
            $details = $event->details ?? [];
            $details['requester_email_queued_at'] = now()->toIso8601String();
            $event->forceFill(['details' => $details])->save();
        } catch (Throwable $exception) {
            report($exception);
            $details = $event->details ?? [];
            $details['requester_email_queue_failed_at'] = now()->toIso8601String();
            $event->forceFill(['details' => $details])->save();
        }
    }

    private function canReceiveDecision(OrganizationDemoRequest $request): bool
    {
        return in_array($request->status, [
            OrganizationDemoRequestStatus::Submitted,
            OrganizationDemoRequestStatus::UnderReview,
            OrganizationDemoRequestStatus::InformationRequested,
            OrganizationDemoRequestStatus::GuidedDemoOffered,
        ], true);
    }

    private function retryAdminSubmissionNotification(OrganizationDemoRequest $request): void
    {
        try {
            $this->workflowNotifications->organizationDemoRequestSubmitted($request);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $mysqlErrorCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23505'
            || ($sqlState === '23000' && $mysqlErrorCode === 1062)
            || str_contains(mb_strtolower($exception->getMessage()), 'unique constraint');
    }
}
