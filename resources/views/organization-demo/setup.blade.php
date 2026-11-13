<x-guest-layout :title="__('Set up your organization account')" :description="__('Create your approved MarHire organization account.')">
    <header class="nw-auth-heading">
        <h1 id="auth-heading">{{ __('Set up your account') }}</h1>
        <p>{{ __('Create the account for :company. This one-time setup link expires :date.', ['company' => $demoRequest->company_name, 'date' => $demoRequest->setup_expires_at?->timezone(config('app.timezone'))->format('M j, Y g:i A')]) }}</p>
    </header>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif


    <form method="POST" action="{{ request()->fullUrl() }}" class="nw-form-stack">
        @csrf
        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="contact_first_name" :value="__('First name')" />
                <x-text-input id="contact_first_name" name="contact_first_name" :value="old('contact_first_name', $demoRequest->contact_first_name)" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('contact_first_name')" />
            </div>
            <div>
                <x-input-label for="contact_last_name" :value="__('Last name')" />
                <x-text-input id="contact_last_name" name="contact_last_name" :value="old('contact_last_name', $demoRequest->contact_last_name)" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('contact_last_name')" />
            </div>
        </div>
        <div>
            <x-input-label for="password" :value="__('Create password')" />
            <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
        </div>
        <button type="submit" class="nw-button nw-button--primary">{{ __('Create account and continue') }} <x-icon name="arrow-right" /></button>
    </form>
</x-guest-layout>
