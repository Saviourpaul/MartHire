<section aria-labelledby="password-heading">
    <header class="nw-panel-heading">
        <h2 id="password-heading">{{ __('Change password') }}</h2>
        <p>{{ __('password changes are completed through a link sent to your email.') }}</p>
    </header>

    <form method="post" action="{{ route('password.reset-link.authenticated') }}" class="nw-form-stack">
        @csrf

        <p class="nw-form-hint">
            {{ __('We will send the reset link to your registered email address:') }}
            <strong>{{ $user->email }}</strong>
        </p>

        <x-input-error id="password_reset-error" :messages="$errors->get('password_reset')" />

        <x-form.actions>
            <x-primary-button>{{ __(' password reset link') }}</x-primary-button>
        </x-form.actions>
    </form>
</section>
