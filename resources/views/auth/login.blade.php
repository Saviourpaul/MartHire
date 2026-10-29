<x-guest-layout>
    <x-slot name="title">{{ __('Log in') }}</x-slot>

    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('Welcome back') }}</span>
        <h1 id="auth-heading">{{ __('Your next chapter awaits.') }}</h1>
        <p>{{ __('Log in to your NearbyWorks account.') }}</p>
    </header>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="nw-form-stack">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <div class="nw-form-row">
            <label for="remember_me" class="nw-checkbox-label">
                <input id="remember_me" class="nw-checkbox" type="checkbox" name="remember" @checked(old('remember'))>
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="nw-link" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <x-primary-button>{{ __('Log in') }} <span aria-hidden="true">&rarr;</span></x-primary-button>
    </form>
  <br>
    

    <a href="{{ route('auth.google') }}" class="nw-button nw-button--outline nw-button--block">
      <img src="{{ asset ('asset/images/google.svg') }}" width="20" height="30" >
      {{ __('Continue with Google') }}
    </a>

    <p class="nw-form-footer">
        {{ __('New to NearbyWorks?') }}
        <a class="nw-link" href="{{ route('register') }}">{{ __('Create an account') }}</a>
    </p>
</x-guest-layout>