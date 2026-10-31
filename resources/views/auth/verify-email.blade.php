<x-guest-layout>
    <x-slot name="title">{{ __('Verify your email') }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('You are almost there') }}</span>
        <h1 id="auth-heading">{{ __('Check your inbox.') }}</h1>
        <p>{{ __('We sent a 6-digit verification code to your email address. Enter it below to confirm your account.') }}
        </p>
    </header>

    <div class="nw-form-stack">
        <form method="POST" action="{{ route('verification.verify') }}" class="nw-form-stack">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Verification code')" />
                <x-text-input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                    maxlength="6" pattern="[0-9]{6}" placeholder="123456" autofocus required />
                <x-input-error id="code-error" :messages="$errors->get('code')" />
            </div>

            <x-primary-button>{{ __('Verify email') }}</x-primary-button>
        </form>

        <p class="nw-form-hint">{{ __('Cannot find the code? Check your spam folder or request another one below.') }}
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="nw-link">{{ __('Resend code') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="nw-form-footer">
            @csrf
            <button type="submit" class="nw-link">{{ __('Log out') }}</button>
        </form>
    </div>
</x-guest-layout>
