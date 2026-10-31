<x-app-layout>
    <x-slot name="title">{{ $interview ? __('Reschedule interview') : __('Schedule interview') }}</x-slot>
    <x-slot name="header"><h1 class="nw-heading">{{ $interview ? __('Reschedule interview') : __('Schedule interview') }}</h1><p>{{ $application->first_name }} {{ $application->last_name }} · {{ $application->job?->title }}</p></x-slot>
    <x-slot name="actions"><a class="nw-link" href="{{ route('organisations.candidates.show', ['applicationForm' => $application, 'tab' => 'interviews']) }}#candidate-interviews">{{ __('Back to candidate') }}</a></x-slot>
    <section class="nw-panel">@include('organisations.interviews._form')</section>
</x-app-layout>
