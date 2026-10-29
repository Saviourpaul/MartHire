<x-layout :title="__('Join :organization', ['organization' => $organization->name])">
    <div class="nw-container">
        <div class="nw-summary-card">
            <h1>{{ __('Join :organization', ['organization' => $organization->name]) }}</h1>
            <p class="nw-copy">{{ __('You’ve been invited as :role for :email.', ['role' => $invitation->role->label(), 'email' => $invitation->email]) }}</p>
            <p class="nw-form-hint">{{ __('Expires :date', ['date' => $invitation->expires_at->format('M j, Y, g:i A')]) }}</p>

            @if ($errors->any())
                <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if ($existingAccount && ! $canAcceptExisting)
                <div class="nw-notice" role="status">
                    {{ __('An account already exists for :email. Log in with that email to accept this invitation.', ['email' => $invitation->email]) }}
                </div>
                <a class="nw-button nw-button--primary" href="{{ route('login') }}">{{ __('Log in') }}</a>
            @elseif (! $existingAccount)
                <form method="POST" action="{{ route('team-invitations.accept', $token) }}" class="nw-form-stack">
                    @csrf
                    <div class="nw-form-row nw-form-row--fields">
                        <div>
                            <x-input-label for="first_name" :value="__('First name')" />
                            <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
                            <x-input-error id="first_name-error" :messages="$errors->get('first_name')" />
                        </div>
                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" />
                            <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                            <x-input-error id="last_name-error" :messages="$errors->get('last_name')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error id="password-error" :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
                    </div>

                    <x-primary-button>{{ __('Accept invitation') }}</x-primary-button>
                </form>
            @else
                <form method="POST" action="{{ route('team-invitations.accept', $token) }}">
                    @csrf
                    <x-primary-button>{{ __('Accept invitation') }}</x-primary-button>
                </form>
            @endif

            <form method="POST" action="{{ route('team-invitations.decline', $token) }}" class="nw-form-hint">
                @csrf
                <button type="submit" class="nw-link">{{ __('Decline invitation') }}</button>
            </form>
        </div>
    </div>
</x-layout>
