<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Personal details') }}"
    data-step-description="{{ __('How the hiring team can reach you.') }}"
    x-show="!ready || step === 0" :aria-hidden="ready && step !== 0">
    <legend data-wizard-heading tabindex="-1">{{ __('Let’s start with you') }}</legend>

    <x-avatar-upload
        :user="$user"
        :required="$profileRequirements['profile_image']"
        :allow-change="$profileRequirements['profile_image']"
        :wizard="true"
        :hint="$profileRequirements['profile_image'] ? __('JPG, PNG, or WebP, up to 5 MB.') : __('Your saved profile photo will be included with this application.')" />
    <div class="nw-form-section__grid">
        @foreach ($personalFields as $field)
            <div class="nw-form-section__item is-half">
                <x-application-field :field="$field" :value="$personalFieldValues[$field['name']] ?? null" />
            </div>
        @endforeach
    </div>
</fieldset>
