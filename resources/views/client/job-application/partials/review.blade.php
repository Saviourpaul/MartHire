<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Review and submit') }}"
    data-step-description="{{ __('Check your application before sending it.') }}"
    x-show="!ready || step === {{ $profileStepEnabled ? 4 : 3 }}"
    :aria-hidden="ready && step !== {{ $profileStepEnabled ? 4 : 3 }}">
    <legend data-wizard-heading tabindex="-1">{{ __('Ready to send?') }}</legend>
    <p class="nw-wizard__description">
        {{ __('Review your details and documents. Use Edit to make any changes before submitting.') }}
    </p>
    <x-form.wizard-review />
    <p class="nw-form-hint">
        {{ __('Your application and documents will be shared with the organization reviewing this role.') }}
    </p>
</fieldset>
