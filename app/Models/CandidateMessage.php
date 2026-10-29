<?php

namespace App\Models;

use App\Enums\CandidateMessageStatus;
use App\Enums\InterviewTemplateType;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use App\Support\CandidateMessageBuilder;
use Database\Factories\CandidateMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CandidateMessage extends Model
{
    /** @use HasFactory<CandidateMessageFactory> */
    use BelongsToOrganization, HasFactory;

    public const DELIVERY_FIELDS = ['status', 'attempts', 'queued_at', 'sent_at', 'failed_at', 'error_message', 'provider_message_id', 'updated_at'];

    protected $fillable = [
        'organization_id', 'interview_id', 'application_form_id', 'email_template_id', 'sent_by', 'type',
        'interview_version', 'to_email', 'from_email', 'from_name', 'reply_to_email', 'reply_to_name',
        'rendered_subject', 'rendered_body', 'rendered_html', 'confirm_url', 'decline_url',
        'status', 'attempts', 'queued_at', 'sent_at', 'failed_at', 'error_message', 'provider_message_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterviewTemplateType::class, 'status' => CandidateMessageStatus::class,
            'interview_version' => 'integer', 'attempts' => 'integer',
            'queued_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime',
        ];
    }

    public function newEloquentBuilder(mixed $query): CandidateMessageBuilder
    {
        return new CandidateMessageBuilder($query);
    }

    protected static function booted(): void
    {
        static::creating(function (CandidateMessage $message): void {
            $interview = Interview::withoutGlobalScope(OrganizationScope::class)->findOrFail($message->interview_id);

            if ((int) $message->application_form_id !== (int) $interview->application_form_id) {
                throw new LogicException('The message must belong to the interview application.');
            }
        });

        static::updating(function (CandidateMessage $message): void {
            if (array_diff(array_keys($message->getDirty()), self::DELIVERY_FIELDS)) {
                throw new LogicException('Candidate email snapshots cannot be changed.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Candidate email history cannot be deleted.');
        });
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by')->withTrashed();
    }

    protected function parentOrganizationId(): ?int
    {
        return Interview::withoutGlobalScope(OrganizationScope::class)->whereKey($this->interview_id)->value('organization_id');
    }
}
