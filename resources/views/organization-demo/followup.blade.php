<x-guest-layout :title="__('Provide more information')" :description="__('Reply to the MarHire team about your organization demo request.')">
    <header class="nw-auth-heading">
        <span class="nw-eyebrow">{{ __('Organization demo request') }}</span>
        <h1 id="auth-heading">{{ __('Provide more information') }}</h1>
        <p>{{ __('Add the details our team requested for :company.', ['company' => $demoRequest->company_name]) }}</p>
    </header>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ request()->fullUrl() }}" class="nw-form-stack">
        @csrf
        <div>
            <x-input-label for="response" :value="__('Additional details')" />
            <textarea id="response" name="response" class="nw-input" rows="6" maxlength="4000" required>{{ old('response') }}</textarea>
            <x-input-error :messages="$errors->get('response')" />
        </div>
        <button class="nw-button nw-button--primary" type="submit">{{ __('Send details') }}</button>
    </form>
</x-guest-layout>
