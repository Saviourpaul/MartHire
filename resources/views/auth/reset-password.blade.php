<x-guest-layout>
    @if ($resetSuccessful ?? false)
        <x-slot name="title">{{ __('Password updated') }}</x-slot>

        <header class="nw-auth-heading">
            <span class="nw-eyebrow">{{ __('Account security') }}</span>
            <h1 id="auth-heading">{{ __('Your password has been updated.') }}</h1>
            <p>{{ __('You can now log in to your Marthire account with your new password.') }}</p>
        </header>

        <p class="nw-form-footer">
            <a class="nw-link" href="{{ route('login') }}">{{ __('Back to log in') }}</a>
        </p>
    @else
        <x-slot name="title">{{ __('Choose a new password') }}</x-slot>

        <header class="nw-auth-heading">
            <span class="nw-eyebrow">{{ __('Account security') }}</span>
            <h1 id="auth-heading">{{ __('Make a fresh start.') }}</h1>
            <p>{{ __('Choose a new password for your Marthire account.') }}</p>
        </header>

        <form method="POST" action="{{ route('password.store') }}" class="nw-form-stack">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $email ?? $request->email)" required autofocus autocomplete="username" />
                <x-input-error id="email-error" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="password" :value="__('New password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-input-error id="password-error" :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
            </div>

            <x-primary-button>{{ __('Reset password') }} <span aria-hidden="true">&rarr;</span></x-primary-button>
        </form>

        <p class="nw-form-footer">
            <a class="nw-link" href="{{ route('login') }}">{{ __('Back to log in') }}</a>
        </p>
    @endif
</x-guest-layout>
