@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-error-300 focus:ring-error-500/10 dark:focus:border-error-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $errorClass = 'mt-2 text-sm text-error-500';
@endphp

<section x-data="{ showDeletePassword: false }">
    <div class="pr-12">
        <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-error-50 text-error-500 dark:bg-error-500/10 dark:text-error-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 6h18" />
                <path d="M8 6V4h8v2" />
                <path d="M19 6l-1 14H6L5 6" />
                <path d="M10 11v6" />
                <path d="M14 11v6" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            {{ __('Delete Account') }}
        </h2>
        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
            {{ __('This action is permanent and cannot be undone. Confirm your password before continuing.') }}
        </p>
    </div>

    <div class="mt-6 rounded-xl border border-error-500/20 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-300">
        {{ __('Deleting your account signs you out immediately and removes access to your profile, dashboard, and submitted records.') }}
    </div>

    <form method="post" action="{{ route('profile.destroy') }}" class="mt-6 space-y-5">
        @csrf
        @method('delete')

        <div>
            <label for="delete_account_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Confirm Password') }}
            </label>
            <div class="relative">
                <input id="delete_account_password" name="password"
                    :type="showDeletePassword ? 'text' : 'password'"
                    class="{{ $inputClass }} pr-12"
                    placeholder="{{ __('Enter your password') }}" autocomplete="current-password" required />
                <button type="button" x-on:click="showDeletePassword = ! showDeletePassword"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    :aria-label="showDeletePassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                    <svg x-show="!showDeletePassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-show="showDeletePassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m2 2 20 20" />
                        <path d="M6.71 6.71A10.4 10.4 0 0 0 2.06 12a1 1 0 0 0 0 .7 10.75 10.75 0 0 0 15.24 5.59" />
                        <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                        <path d="M12 5c4.48 0 8.27 2.94 9.94 7a10.6 10.6 0 0 1-2.19 3.22" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->userDeletion->get('password')" class="{{ $errorClass }}" />
        </div>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="isDeleteAccountModal = false"
                class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                {{ __('Cancel') }}
            </button>
            <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-error-600 focus:outline-hidden focus:ring-3 focus:ring-error-500/30 sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <path d="M3 6h18" />
                    <path d="M8 6V4h8v2" />
                    <path d="M19 6l-1 14H6L5 6" />
                </svg>
                {{ __('Delete Account') }}
            </button>
        </div>
    </form>
</section>
