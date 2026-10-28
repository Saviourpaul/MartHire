<section aria-labelledby="password-heading">
    <header class="nw-panel-heading">
        <span class="nw-eyebrow">{{ __('Peace of mind') }}</span>
        <h2 id="password-heading">{{ __('Change password') }}</h2>
        <p>{{ __('Use a long, unique password to help keep your account secure.') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="nw-form-stack">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current password')" />
            <x-text-input id="update_password_current_password" name="current_password" error-bag="updatePassword" type="password" autocomplete="current-password" />
            <x-input-error id="update_password_current_password-error" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New password')" />
            <x-text-input id="update_password_password" name="password" error-bag="updatePassword" type="password" autocomplete="new-password" />
            <x-input-error id="update_password_password-error" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" error-bag="updatePassword" type="password" autocomplete="new-password" />
            <x-input-error id="update_password_password_confirmation-error" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <x-form.actions>
            <x-primary-button>{{ __('Update password') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="nw-form-hint" role="status">{{ __('Your password has been updated.') }}</p>
            @endif
        </x-form.actions>
    </form>
</section>