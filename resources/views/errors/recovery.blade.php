<x-guest-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('Link expired') }}</span>
        <h1 id="auth-heading">{{ $title }}</h1>
        <p>{{ $message }}</p>
    </header>

    @if (! empty($help))
        <p class="nw-form-hint">{{ $help }}</p>
    @endif

    <a href="{{ $actionUrl }}" class="nw-button nw-button--primary nw-button--block">
        {{ $actionLabel }}
    </a>
</x-guest-layout>
