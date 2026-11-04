<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Personal details') }}"
    data-step-description="{{ __('How the hiring team can reach you.') }}"
    x-show="!ready || step === 0" :aria-hidden="ready && step !== 0">
    <legend data-wizard-heading tabindex="-1">{{ __('Let’s start with you') }}</legend>

    <x-avatar-upload :user="$user" :required="blank($user->profile_image_path)" :wizard="true" :hint="__(
        'JPG, PNG or WEBP. Up to 5 MB, between 200 × 200 and 2000 × 2000 pixels. Your saved photo is used when no replacement is selected.',
    )" />
    <div class="nw-form-section__grid">
        @foreach ($personalFields as $field)
            <div class="nw-form-section__item is-half">
                <x-application-field :field="$field" :value="$personalFieldValues[$field['name']] ?? null" />
            </div>
        @endforeach
    </div>
</fieldset>
