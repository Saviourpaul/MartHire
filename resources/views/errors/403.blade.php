<x-guest-layout>
    <x-slot name="title">{{ __('Access denied') }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('Access denied') }}</span>
        <h1 id="auth-heading">{{ __("You don't have access to this page.") }}</h1>
        <p>{{ __('Your account does not have permission to view this page.') }}</p>
    </header>

    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="nw-button nw-button--primary nw-button--block">
        {{ auth()->check() ? __('Go to your dashboard') : __('Log in') }}
    </a>
</x-guest-layout>
