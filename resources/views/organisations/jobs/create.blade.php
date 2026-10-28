<x-app-layout>
    <x-slot name="title">{{ __('Post a job') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Post a job') }}</h1>
        <p>{{ __('Create a new role for your organization.') }}</p>
    </x-slot>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="nw-panel">
        @include('organisations.jobs._form')
    </section>
</x-app-layout>
