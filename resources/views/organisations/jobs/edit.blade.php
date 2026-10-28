<x-app-layout>
    <x-slot name="title">{{ __('Edit job') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Edit job') }}</h1>
        <p>{{ $job->title }}</p>
    </x-slot>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="nw-panel">
        @include('organisations.jobs._form')
    </section>
</x-app-layout>
