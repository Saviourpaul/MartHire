<?php

namespace App\Services;

use App\Enums\InterviewMode;
use App\Enums\InterviewTemplateType;
use App\Models\Interview;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InterviewTemplateRenderer
{
    public const PLACEHOLDERS = [
        'candidate_name', 'job_title', 'organization_name', 'interview_date', 'interview_time',
        'timezone', 'venue_name', 'address', 'meeting_link', 'contact_name', 'contact_phone', 'instructions',
    ];

    private const TOKEN_PATTERN = '/(?<!\{)\{\{\s*([a-z_]+)\s*\}\}(?!\})/u';

    public function __construct(private readonly EmailTemplateService $templates) {}

    public function validate(InterviewTemplateType $type, string $subject, string $body): void
    {
        $errors = [];

        if (trim($subject) === '' || Str::length($subject) > (int) config('interviews.subject_max_length', 255) || preg_match('/[\r\n\x00]/', $subject)) {
            $errors['subject'] = 'Use a subject of up to 255 characters without line breaks.';
        }

        if (trim($body) === '' || Str::length($body) > (int) config('interviews.body_max_length', 20000) || str_contains($body, "\0")) {
            $errors['body'] = 'Use a message of up to 20,000 characters.';
        }

        foreach (['subject' => $subject, 'body' => $body] as $field => $content) {
            preg_match_all(self::TOKEN_PATTERN, $content, $matches);

            if (array_diff($matches[1], self::PLACEHOLDERS)) {
                $errors[$field] = 'Use only the supported interview placeholders.';
            }

            $remaining = preg_replace(self::TOKEN_PATTERN, '', $content);

            if (Str::contains($remaining, ['{{', '}}', '{!!', '!!}'])) {
                $errors[$field] = 'Use placeholders in the format {{ candidate_name }}.';
            }
        }

        $required = match ($type) {
            InterviewTemplateType::InterviewOnline => 'meeting_link',
            InterviewTemplateType::InterviewOnsite => 'address',
            default => null,
        };

        if ($required && ! $this->containsPlaceholder($body, $required)) {
            $errors['body'] = 'Include the {{ '.$required.' }} placeholder in the message.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return array{subject: string, body: string} */
    public function render(Interview $interview, InterviewTemplateType $type, ?string $subject = null, ?string $body = null): array
    {
        $template = $this->templates->resolve($interview->organization_id, $type);
        $subject ??= $template->subject;
        $body ??= $template->body;
        // Reschedule templates are shared by online and onsite interviews. Always
        // retain the current meeting destination when a generic template omits it.
        if ($type === InterviewTemplateType::InterviewReschedule) {
            $destination = $interview->mode === InterviewMode::Online ? 'meeting_link' : 'address';
            if (! $this->containsPlaceholder($body, $destination)) {
                $body .= "\n\n".($destination === 'meeting_link' ? 'Join here: ' : 'Location: ').'{{ '.$destination.' }}';
            }
        }
        $this->validate($type, $subject, $body);
        $interview->loadMissing(['applicationForm.job', 'organization']);

        if ($type !== InterviewTemplateType::InterviewCancel) {
            $required = $interview->mode === InterviewMode::Online ? 'meeting_link' : 'address';

            if (blank($interview->{$required}) || ! $this->containsPlaceholder($body, $required)) {
                throw ValidationException::withMessages(['body' => 'Include the interview '.$required.' and its placeholder.']);
            }

            if ($required === 'meeting_link' && (! filter_var($interview->meeting_link, FILTER_VALIDATE_URL)
                || ! in_array(strtolower((string) parse_url($interview->meeting_link, PHP_URL_SCHEME)), ['http', 'https'], true))) {
                throw ValidationException::withMessages(['meeting_link' => 'Use a valid HTTP or HTTPS meeting link.']);
            }
        }

        $application = $interview->applicationForm;
        $start = $interview->start_at->setTimezone($interview->timezone);
        $end = $interview->end_at->setTimezone($interview->timezone);
        $instructions = collect([
            $interview->instructions,
            filled($interview->what_to_bring) ? 'What to bring: '.$interview->what_to_bring : null,
            filled($interview->directions) ? 'Directions: '.$interview->directions : null,
        ])->filter(fn (?string $value): bool => filled($value))->implode("\n\n");
        $values = [
            'candidate_name' => trim(collect([$application->first_name, $application->middle_name, $application->last_name])->filter()->implode(' ')),
            'job_title' => $application->job->title, 'organization_name' => $interview->organization->name,
            'interview_date' => $start->format('l, F j, Y'), 'interview_time' => $start->format('g:i A').' - '.$end->format('g:i A'),
            'timezone' => $interview->timezone, 'venue_name' => $interview->venue_name ?? '', 'address' => $interview->address ?? '',
            'meeting_link' => $interview->meeting_link ?? '', 'contact_name' => $interview->contact_name ?? '',
            'contact_phone' => $interview->contact_phone ?? '', 'instructions' => $instructions,
        ];
        $replace = static fn (string $content): string => preg_replace_callback(self::TOKEN_PATTERN, static fn (array $match): string => $values[$match[1]], $content);
        $renderedSubject = $replace($subject);
        $renderedBody = $replace($body);

        if (preg_match('/[\r\n\x00]/', $renderedSubject) || Str::length($renderedSubject) > (int) config('interviews.subject_max_length', 255)) {
            throw ValidationException::withMessages(['subject' => 'The rendered subject must fit on one line and be no longer than 255 characters.']);
        }

        if (! $this->containsPlaceholder($body, 'instructions') && filled($instructions)) {
            $renderedBody .= "\n\n".$instructions;
        }

        if (Str::length($renderedBody) > (int) config('interviews.body_max_length', 20000)) {
            throw ValidationException::withMessages(['body' => 'The rendered message must be no longer than 20,000 characters.']);
        }

        return ['subject' => $renderedSubject, 'body' => $renderedBody];
    }

    private function containsPlaceholder(string $content, string $name): bool
    {
        return preg_match('/(?<!\{)\{\{\s*'.preg_quote($name, '/').'\s*\}\}(?!\})/u', $content) === 1;
    }
}
