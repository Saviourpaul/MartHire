<section aria-labelledby="delete-account-heading">
    <header class="nw-panel-heading">
        <span class="nw-eyebrow">{{ __('Account management') }}</span>
        <h2 id="delete-account-heading">{{ __('Delete account') }}</h2>
        <p>{{ __('Permanently delete your account and its data. Save any information you want to keep before continuing.') }}</p>
    </header>

    <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete account') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="nw-modal-content">
            @csrf
            @method('delete')

            <header class="nw-panel-heading">
                <span class="nw-eyebrow">{{ __('Please confirm') }}</span>
                <h2 id="confirm-user-deletion-title">{{ __('Delete your account?') }}</h2>
                <p id="confirm-user-deletion-description">{{ __('This permanently deletes your account and its data. Enter your password to confirm.') }}</p>
            </header>

            <div class="nw-form-stack">
                <div>
                    <x-input-label for="delete_account_password" :value="__('Password')" />
                    <x-text-input id="delete_account_password" name="password" error-bag="userDeletion" type="password" autocomplete="current-password" />
                    <x-input-error id="delete_account_password-error" :messages="$errors->userDeletion->get('password')" />
                </div>

                <x-form.actions>
                    <x-slot:cancel>
                        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                    </x-slot:cancel>
                    <x-danger-button>{{ __('Delete account') }}</x-danger-button>
                </x-form.actions>
            </div>
        </form>
    </x-modal>
</section>