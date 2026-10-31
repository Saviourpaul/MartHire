<x-guest-layout>
    <h1 class="nw-heading" id="auth-heading">{{ $response === \App\Enums\InterviewCandidateResponse::Confirmed ? __('Confirm your interview') : __('Decline this interview') }}</h1>
    <p>{{ $application->job?->title }}</p>
    <p>{{ $interview->start_at->setTimezone($interview->timezone)->format('l, j F Y · g:i A') }} ({{ $interview->timezone }})</p>
    <p>{{ __('Your response will be shared with the hiring team.') }}</p>
    <form method="POST" action="{{ $actionUrl }}" class="nw-form">@csrf<button type="submit" class="nw-button nw-button--primary">{{ $response === \App\Enums\InterviewCandidateResponse::Confirmed ? __('Confirm attendance') : __('Decline invitation') }}</button></form>
</x-guest-layout>
