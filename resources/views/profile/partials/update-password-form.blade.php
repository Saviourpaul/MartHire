@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $labelClass = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $errorClass = 'mt-2 text-sm text-error-500';
@endphp

<section x-data="{ showCurrentPassword: false, showNewPassword: false, showConfirmPassword: false }">
    <div class="pr-12">
        <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            {{ __('Change Password') }}
        </h2>
        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
            {{ __('Enter your current password and choose a strong new password to keep your account secure.') }}
        </p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="{{ $labelClass }}">
                {{ __('Current Password') }}
            </label>
            <div class="relative">
                <input id="update_password_current_password" name="current_password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    class="{{ $inputClass }} pr-12" autocomplete="current-password" required />
                <button type="button" x-on:click="showCurrentPassword = ! showCurrentPassword"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    :aria-label="showCurrentPassword ? '{{ __('Hide current password') }}' : '{{ __('Show current password') }}'">
                    <svg x-show="!showCurrentPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-show="showCurrentPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m2 2 20 20" />
                        <path d="M6.71 6.71A10.4 10.4 0 0 0 2.06 12a1 1 0 0 0 0 .7 10.75 10.75 0 0 0 15.24 5.59" />
                        <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                        <path d="M14.12 14.12 9.88 9.88" />
                        <path d="M12 5c4.48 0 8.27 2.94 9.94 7a10.6 10.6 0 0 1-2.19 3.22" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="{{ $errorClass }}" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="{{ $labelClass }}">
                    {{ __('New Password') }}
                </label>
                <div class="relative">
                    <input id="update_password_password" name="password" :type="showNewPassword ? 'text' : 'password'"
                        class="{{ $inputClass }} pr-12" autocomplete="new-password" required />
                    <button type="button" x-on:click="showNewPassword = ! showNewPassword"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        :aria-label="showNewPassword ? '{{ __('Hide new password') }}' : '{{ __('Show new password') }}'">
                        <svg x-show="!showNewPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="showNewPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2 2 20 20" />
                            <path d="M6.71 6.71A10.4 10.4 0 0 0 2.06 12a1 1 0 0 0 0 .7 10.75 10.75 0 0 0 15.24 5.59" />
                            <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                            <path d="M12 5c4.48 0 8.27 2.94 9.94 7a10.6 10.6 0 0 1-2.19 3.22" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="{{ $errorClass }}" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="{{ $labelClass }}">
                    {{ __('Confirm New Password') }}
                </label>
                <div class="relative">
                    <input id="update_password_password_confirmation" name="password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        class="{{ $inputClass }} pr-12" autocomplete="new-password" required />
                    <button type="button" x-on:click="showConfirmPassword = ! showConfirmPassword"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        :aria-label="showConfirmPassword ? '{{ __('Hide password confirmation') }}' : '{{ __('Show password confirmation') }}'">
                        <svg x-show="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2 2 20 20" />
                            <path d="M6.71 6.71A10.4 10.4 0 0 0 2.06 12a1 1 0 0 0 0 .7 10.75 10.75 0 0 0 15.24 5.59" />
                            <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                            <path d="M12 5c4.48 0 8.27 2.94 9.94 7a10.6 10.6 0 0 1-2.19 3.22" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="{{ $errorClass }}" />
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
            {{ __('Use a unique password with at least 8 characters. ') }}
        </div>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="isChangePasswordModal = false"
                class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                {{ __('Cancel') }}
            </button>
            <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/30 sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                {{ __('Update Password') }}
            </button>
        </div>
    </form>
</section>
