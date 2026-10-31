<x-guest-layout>
    <h1 class="nw-heading" id="auth-heading">{{ __('Response recorded') }}</h1>
    <p>{{ $response === \App\Enums\InterviewCandidateResponse::Confirmed ? __('Your attendance has been confirmed.') : __('You have declined this interview.') }}</p>
    <p>{{ __('The hiring team has been notified.') }}</p>
    <p>{{ $application->job?->title }} · {{ $interview->start_at->setTimezone($interview->timezone)->format('j M Y, g:i A') }} ({{ $interview->timezone }})</p>
    <a class="nw-link" href="{{ route('home') }}">{{ __('Back to Marthire') }}</a>
</x-guest-layout>
