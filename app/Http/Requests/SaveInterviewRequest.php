<?php

namespace App\Http\Requests;

use App\Enums\InterviewMode;
use App\Enums\InterviewPlatform;
use App\Models\ApplicationForm;
use App\Models\Interview;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        if ($interview instanceof Interview) {
            return $this->user()?->can('update', $interview) ?? false;
        }

        $application = $this->route('applicationForm');

        if (! $application instanceof ApplicationForm) {
            return false;
        }

        if ($this->routeIs('organisations.interviews.preview') && $this->filled('interview_id')) {
            return $this->user()?->can('send_candidate_email', $application) ?? false;
        }

        return $this->user()?->can('create', [Interview::class, $application]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(InterviewMode::class)],
            'interview_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone:all'],
            'platform' => ['exclude_unless:mode,online', 'required', Rule::enum(InterviewPlatform::class)],
            'meeting_link' => ['exclude_unless:mode,online', 'required', 'string', 'url:https', 'max:2000'],
            'venue_name' => ['exclude_unless:mode,onsite', 'required', 'string', 'max:255'],
            'address' => ['exclude_unless:mode,onsite', 'required', 'string', 'max:2000'],
            'what_to_bring' => ['exclude_unless:mode,onsite', 'nullable', 'string', 'max:2000'],
            'directions' => ['exclude_unless:mode,onsite', 'nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'body' => ['nullable', 'string', 'max:20000'],
            'move_to_interview' => ['prohibited'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'interview_id' => ['sometimes', 'integer', 'min:1'],
            'to_email' => ['prohibited'],
            'recipient' => ['prohibited'],
            'cc' => ['prohibited'],
            'bcc' => ['prohibited'],
            'attachments' => ['prohibited'],
            'organization_id' => ['prohibited'],
            'application_form_id' => ['prohibited'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $date = $this->string('interview_date')->toString();
            $timezone = $this->string('timezone')->toString();
            $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$this->input('start_time'), $timezone);
            $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$this->input('end_time'), $timezone);

            if ($start->format('Y-m-d H:i') !== $date.' '.$this->input('start_time')
                || $end->format('Y-m-d H:i') !== $date.' '.$this->input('end_time')) {
                $validator->errors()->add('start_time', 'This local time does not exist in the selected timezone.');

                return;
            }

            if ($start->lessThanOrEqualTo(now())) {
                $validator->errors()->add('start_time', 'Choose an interview start time in the future.');
            }

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('end_time', 'The end time must be after the start time on the interview date.');
            }
        }];
    }
}
