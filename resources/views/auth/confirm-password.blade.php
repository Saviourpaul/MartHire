<x-guest-layout>
    <x-slot name="title">{{ __('Confirm your password') }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('One quick check') }}</span>
        <h1 id="auth-heading">{{ __('Confirm it is you.') }}</h1>
        <p>{{ __('This is a secure area of Marthire. Confirm your password to continue.') }}</p>
    </header>

    <form method="POST" action="{{ route('password.confirm') }}" class="nw-form-stack">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <x-primary-button>{{ __('Confirm password') }} <span aria-hidden="true">&rarr;</span></x-primary-button>
    </form>

    <p class="nw-form-footer">
        <a class="nw-link" href="{{ route('dashboard') }}">{{ __('Back to your account') }}</a>
    </p>
</x-guest-layout>