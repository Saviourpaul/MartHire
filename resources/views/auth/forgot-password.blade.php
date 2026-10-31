<x-guest-layout>
    <x-slot name="title">{{ __('Reset your password') }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('A fresh start') }}</span>
        <h1 id="auth-heading">{{ __('Forgot your password?') }}</h1>
        <p>{{ __('Enter your email address and we will send you a link to choose a new password.') }}</p>
    </header>

    <form method="POST" action="{{ route('password.email') }}" class="nw-form-stack">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <x-primary-button>{{ __('Send reset link') }} <span aria-hidden="true">&rarr;</span></x-primary-button>
    </form>

    <p class="nw-form-footer">
        <a class="nw-link" href="{{ route('login') }}">{{ __('Back to log in') }}</a>
    </p>
</x-guest-layout>
