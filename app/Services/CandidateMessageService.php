<?php

namespace App\Services;

use App\Enums\CandidateMessageStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use App\Enums\InterviewTemplateType;
use App\Jobs\SendCandidateInterviewEmail;
use App\Mail\CandidateInterviewEmail;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class CandidateMessageService
{
    public function __construct(
        private readonly InterviewTemplateRenderer $renderer,
        private readonly EmailTemplateService $templates,
        private readonly OrganizationContext $context,
        private readonly ActivityLogger $logs,
        private readonly WorkflowNotificationService $notifications,
    ) {}

    public function queue(Interview $interview, User $actor, InterviewTemplateType $type, ?string $subject = null, ?string $body = null): CandidateMessage
    {
        $interview->loadMissing('applicationForm');
        Gate::forUser($actor)->authorize('send_candidate_email', $interview->applicationForm);

        return DB::transaction(function () use ($interview, $actor, $type, $subject, $body): CandidateMessage {
            $organization = Organization::whereKey($interview->organization_id)->lockForUpdate()->firstOrFail();

            return $this->context->runInOrganization($organization, function () use ($interview, $actor, $type, $subject, $body): CandidateMessage {
                ApplicationForm::whereKey($interview->application_form_id)->lockForUpdate()->firstOrFail();
                $interview = Interview::query()->with('applicationForm.job', 'organization')->lockForUpdate()->findOrFail($interview->id);
                Gate::forUser($actor)->authorize('send_candidate_email', $interview->applicationForm);
                $this->ensureSendableInterview($interview, $type);
                $recipient = $interview->applicationForm->email;

                if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! filter_var($actor->email, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(['email' => 'The candidate and sender must have valid email addresses.']);
                }

                $rendered = $this->renderer->render($interview, $type, $subject, $body);
                $template = $this->templates->resolve($interview->organization_id, $type);
                $urls = $this->responseUrls($interview, $type);
                $replyToName = trim($actor->first_name.' '.$actor->last_name);

                if (preg_match('/[\r\n\x00]/', $replyToName)) {
                    throw ValidationException::withMessages(['email' => 'The sender name cannot contain line breaks.']);
                }

                $this->consumeSendingLimit($actor, $interview->organization_id);
                $message = CandidateMessage::create([
                    'organization_id' => $interview->organization_id, 'interview_id' => $interview->id,
                    'application_form_id' => $interview->application_form_id, 'email_template_id' => $template->id,
                    'sent_by' => $actor->id, 'type' => $type, 'interview_version' => $interview->version,
                    'to_email' => $recipient, 'from_email' => config('mail.from.address'), 'from_name' => $interview->organization->name.' via Marthire',
                    'reply_to_email' => $actor->email, 'reply_to_name' => $replyToName,
                    'rendered_subject' => $rendered['subject'], 'rendered_body' => $rendered['body'],
                    'rendered_html' => view('emails.interview-message', [
                        'subject' => $rendered['subject'], 'body' => $rendered['body'],
                        'organizationName' => $interview->organization->name, ...$urls,
                    ])->render(),
                    'confirm_url' => $urls['confirmUrl'], 'decline_url' => $urls['declineUrl'],
                    'status' => CandidateMessageStatus::Queued, 'attempts' => 0, 'queued_at' => now(),
                ]);
                $this->record($message, $actor, 'candidate_email_queued');
                $this->dispatchAfterCommit($message);

                return $message;
            });
        });
    }

    public function retry(CandidateMessage $message, User $actor): CandidateMessage
    {
        Gate::forUser($actor)->authorize('retry', $message);

        return DB::transaction(function () use ($message, $actor): CandidateMessage {
            $organization = Organization::whereKey($message->organization_id)->lockForUpdate()->firstOrFail();

            return $this->context->runInOrganization($organization, function () use ($message, $actor): CandidateMessage {
                ApplicationForm::whereKey($message->application_form_id)->lockForUpdate()->firstOrFail();
                $interview = Interview::query()->lockForUpdate()->findOrFail($message->interview_id);
                $message = CandidateMessage::query()->lockForUpdate()->findOrFail($message->id);
                Gate::forUser($actor)->authorize('retry', $message);

                if ($message->status !== CandidateMessageStatus::Failed || $message->interview_version !== $interview->version || $this->linksExpired($message)) {
                    throw ValidationException::withMessages(['message' => 'Only a failed message for the current interview with valid response links can be retried.']);
                }

                $this->ensureSendableInterview($interview, $message->type);
                $this->consumeSendingLimit($actor, $message->organization_id);
                $message->update(['status' => CandidateMessageStatus::Queued, 'queued_at' => now(), 'failed_at' => null, 'error_message' => null]);
                $this->record($message, $actor, 'candidate_email_retried');
                $this->dispatchAfterCommit($message);

                return $message;
            });
        });
    }

    public function deliver(int $messageId, int $organizationId): void
    {
        $initialMessage = CandidateMessage::withoutGlobalScope(OrganizationScope::class)->where('organization_id', $organizationId)->find($messageId);

        if (! $initialMessage) {
            return;
        }

        $organization = Organization::findOrFail($organizationId);
        $this->context->runInOrganization($organization, function () use ($initialMessage): void {
            $failure = null;
            DB::transaction(function () use ($initialMessage, &$failure): void {
                Organization::whereKey($initialMessage->organization_id)->lockForUpdate()->firstOrFail();
                ApplicationForm::whereKey($initialMessage->application_form_id)->lockForUpdate()->firstOrFail();
                $interview = Interview::query()->lockForUpdate()->find($initialMessage->interview_id);
                $message = CandidateMessage::query()->lockForUpdate()->findOrFail($initialMessage->id);

                if ($message->status !== CandidateMessageStatus::Queued) {
                    return;
                }

                if (! $interview || $message->interview_version !== $interview->version
                    || ($message->type !== InterviewTemplateType::InterviewCancel && ! in_array($interview->status, [InterviewStatus::Scheduled, InterviewStatus::Rescheduled], true))
                    || ($message->type === InterviewTemplateType::InterviewCancel && $interview->status !== InterviewStatus::Cancelled)) {
                    $this->failMessage($message, 'This message was not sent because the interview details have changed.');

                    return;
                }

                if ($message->type !== InterviewTemplateType::InterviewCancel && ($interview->end_at->isPast() || $this->linksExpired($message))) {
                    $this->failMessage($message, 'This invitation was not sent because its response window has expired.');

                    return;
                }

                $message->update(['attempts' => $message->attempts + 1, 'error_message' => null]);

                try {
                    $sent = Mail::to($message->to_email)->send(new CandidateInterviewEmail($message));
                    $message->update([
                        'status' => CandidateMessageStatus::Sent, 'sent_at' => now(), 'failed_at' => null,
                        'error_message' => null, 'provider_message_id' => $sent?->getMessageId(),
                    ]);
                    $this->record($message, null, 'candidate_email_sent');
                } catch (Throwable $exception) {
                    $message->update(['error_message' => 'Delivery failed temporarily. Automatic retries are pending.']);
                    Log::warning('Candidate interview email delivery attempt failed.', [
                        'message_id' => $message->id, 'organization_id' => $message->organization_id, 'exception_type' => $exception::class,
                    ]);
                    $failure = $exception;
                }
            });

            if ($failure) {
                throw $failure;
            }
        });
    }

    public function markFailed(int $messageId, int $organizationId, Throwable $exception): void
    {
        Log::error('Candidate interview email delivery failed permanently.', [
            'message_id' => $messageId, 'organization_id' => $organizationId, 'exception_type' => $exception::class,
        ]);
        $organization = Organization::find($organizationId);

        if (! $organization) {
            return;
        }

        $this->context->runInOrganization($organization, function () use ($messageId): void {
            DB::transaction(function () use ($messageId): void {
                $initialMessage = CandidateMessage::query()->find($messageId);

                if (! $initialMessage) {
                    return;
                }

                Organization::whereKey($initialMessage->organization_id)->lockForUpdate()->firstOrFail();
                ApplicationForm::whereKey($initialMessage->application_form_id)->lockForUpdate()->firstOrFail();
                Interview::whereKey($initialMessage->interview_id)->lockForUpdate()->firstOrFail();
                $message = CandidateMessage::query()->lockForUpdate()->find($messageId);

                if ($message?->status === CandidateMessageStatus::Queued) {
                    $activity = $this->failMessage($message, 'The email could not be sent. Please retry or contact support.');
                    $this->notifications->candidateEmailFailed($message, $activity->id);
                }
            });
        });
    }

    private function dispatchAfterCommit(CandidateMessage $message): void
    {
        $messageId = $message->id;
        $organizationId = $message->organization_id;
        DB::afterCommit(function () use ($messageId, $organizationId): void {
            $job = new SendCandidateInterviewEmail($messageId, $organizationId);

            try {
                $dispatch = dispatch($job);
                unset($dispatch);
            } catch (Throwable $exception) {
                (new UniqueLock(app(CacheRepository::class)))->release($job);
                $this->markFailed($messageId, $organizationId, $exception);
            }
        });
    }

    private function ensureSendableInterview(Interview $interview, InterviewTemplateType $type): void
    {
        if (($type === InterviewTemplateType::InterviewOnline && $interview->mode !== InterviewMode::Online)
            || ($type === InterviewTemplateType::InterviewOnsite && $interview->mode !== InterviewMode::Onsite)) {
            throw ValidationException::withMessages(['type' => 'Choose a template that matches the interview mode.']);
        }

        if (($type === InterviewTemplateType::InterviewCancel && $interview->status !== InterviewStatus::Cancelled)
            || ($type !== InterviewTemplateType::InterviewCancel && (! in_array($interview->status, [InterviewStatus::Scheduled, InterviewStatus::Rescheduled], true) || $interview->end_at->isPast()))) {
            throw ValidationException::withMessages(['interview' => 'This message does not match a current interview.']);
        }
    }

    /** @return array{confirmUrl: ?string, declineUrl: ?string} */
    private function responseUrls(Interview $interview, InterviewTemplateType $type): array
    {
        if ($type === InterviewTemplateType::InterviewCancel) {
            return ['confirmUrl' => null, 'declineUrl' => null];
        }

        $maximum = now()->addDays((int) config('interviews.response_link_days', 30));
        $expires = $interview->end_at->lessThan($maximum) ? $interview->end_at : $maximum;
        $url = static fn (string $response): string => URL::temporarySignedRoute('interviews.response', $expires, [
            'interview' => $interview->id, 'response' => $response, 'version' => $interview->version,
        ]);

        return ['confirmUrl' => $url('confirmed'), 'declineUrl' => $url('declined')];
    }

    private function linksExpired(CandidateMessage $message): bool
    {
        return $message->type !== InterviewTemplateType::InterviewCancel
            && (! $message->confirm_url || ! URL::hasValidSignature(Request::create($message->confirm_url)));
    }

    private function consumeSendingLimit(User $actor, int $organizationId): void
    {
        $limits = [
            'candidate-email:user:'.$actor->id => (int) config('interviews.user_send_limit', 20),
            'candidate-email:organization:'.$organizationId => (int) config('interviews.org_send_limit', 100),
        ];

        foreach ($limits as $key => $maximum) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                throw ValidationException::withMessages(['email' => 'The hourly email limit has been reached. Please try again later.']);
            }
        }

        foreach ($limits as $key => $maximum) {
            RateLimiter::hit($key, 3600);
        }
    }

    private function failMessage(CandidateMessage $message, string $error): ActivityLog
    {
        $message->update(['status' => CandidateMessageStatus::Failed, 'failed_at' => now(), 'error_message' => $error]);

        return $this->record($message, null, 'candidate_email_failed');
    }

    private function record(CandidateMessage $message, ?User $actor, string $action): ActivityLog
    {
        return $this->logs->record($message->organization_id, $message->applicationForm, $actor, 'email', [
            'action' => $action, 'candidate_message_id' => $message->id, 'interview_id' => $message->interview_id,
            'template_type' => $message->type->value, 'status' => $message->status->value,
            'interview_version' => $message->interview_version,
        ]);
    }
}
