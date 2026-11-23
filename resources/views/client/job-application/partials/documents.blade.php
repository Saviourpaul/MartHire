<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Documents') }}"
    data-step-description="{{ __('Share the documents needed to review your application.') }}"
    x-show="!ready || step === {{ $documentsStepIndex }}"
    :aria-hidden="ready && step !== {{ $documentsStepIndex }}">
    <legend data-wizard-heading tabindex="-1">{{ __('Share your documents') }}</legend>
    <p class="nw-wizard__description">
        {{ __('A résumé, profile photo, and government-issued identification are required. Identification is stored privately and shared only with authorized hiring team members. No national ID number is requested.') }}
    </p>

    @if (filled($identityRequirementReason))
        <div class="nw-notice nw-notice--info" role="note">
            <strong>{{ __('Additional identification context for this role') }}</strong>
            <p>{{ $identityRequirementReason }}</p>
        </div>
    @endif

    <div class="nw-form-section__grid">
        @if ($savedIdentityDocument)
            <div class="nw-field nw-form-section__item" data-field="identification_document" data-type="file"
                data-label="{{ __('Government identification document') }}" data-required>
                <span class="nw-label">{{ __('Government identification document') }} <span class="nw-required" aria-hidden="true">*</span></span>
                <p>{{ $savedIdentityDocument->document_type->label() }} · {{ $savedIdentityDocument->original_name }}</p>
                <p class="nw-form-hint">{{ __('Your saved document will be copied into this application.') }}</p>
            </div>

            @if (filled($savedIdentityDocument->issuing_country_code))
                <div class="nw-field nw-form-section__item" data-field="identity_issuing_country_code"
                    data-type="dropdown" data-label="{{ __('Issuing country') }}" data-required>
                    <span class="nw-label">{{ __('Issuing country') }} <span class="nw-required" aria-hidden="true">*</span></span>
                    <p>{{ $savedIdentityIssuingCountry ?: $savedIdentityDocument->issuing_country_code }}</p>
                </div>
            @else
                <div class="nw-field nw-form-section__item" data-field="identity_issuing_country_code"
                    data-type="dropdown" data-label="{{ __('Issuing country') }}" data-required>
                    <label class="nw-label" for="identity_issuing_country_code">{{ __('Issuing country') }} <span class="nw-required" aria-hidden="true">*</span></label>
                    <select class="nw-select" id="identity_issuing_country_code" name="identity_issuing_country_code"
                        required x-bind:aria-invalid="error('identity_issuing_country_code') ? 'true' : 'false'"
                        x-on:change="clear('identity_issuing_country_code')">
                        <option value="">{{ __('Select issuing country') }}</option>
                        @foreach ($identityCountryChoices as $country)
                            <option value="{{ $country['id'] }}" @selected(old('identity_issuing_country_code') === $country['id'])>{{ $country['body'] }}</option>
                        @endforeach
                    </select>
                    <p class="nw-field__error" role="alert" x-show="error('identity_issuing_country_code')" x-text="error('identity_issuing_country_code')" x-cloak></p>
                </div>
            @endif
        @else
            <div class="nw-field nw-form-section__item" data-field="identification_type"
                data-type="dropdown" data-label="{{ __('Identification document type') }}" data-required>
                <label class="nw-label" for="identification_type">{{ __('Identification document type') }} <span class="nw-required" aria-hidden="true">*</span></label>
                <select class="nw-select" id="identification_type" name="identification_type" required
                    x-bind:aria-invalid="error('identification_type') ? 'true' : 'false'"
                    x-on:change="clear('identification_type')">
                    <option value="">{{ __('Choose an identification document') }}</option>
                    @foreach ($identityField['choices'] as $choice)
                        <option value="{{ $choice['id'] }}" @selected((string) old('identification_type') === (string) $choice['id'])>{{ $choice['body'] }}</option>
                    @endforeach
                </select>
                <p class="nw-field__error" role="alert" x-show="error('identification_type')" x-text="error('identification_type')" x-cloak></p>
            </div>

            <div class="nw-field nw-form-section__item" data-field="identification_document"
                data-type="file" data-label="{{ __('Government identification document') }}"
                data-accept="pdf,jpg,jpeg,png" data-max="5242880" data-required>
                <span class="nw-label">{{ __('Upload government identification') }} <span class="nw-required" aria-hidden="true">*</span></span>
                <p class="nw-hint">{{ __('National identity card, international passport, driver’s license, or another government-issued ID. No NIN or BVN number is needed.') }}</p>
                <label class="nw-dropzone" for="identification_document">
                    <input class="nw-sr-only" id="identification_document" type="file" name="identification_document"
                        required accept=".pdf,.jpg,.jpeg,.png" aria-describedby="identification_document-error"
                        x-bind:aria-invalid="error('identification_document') ? 'true' : 'false'" x-on:change="pick($event)">
                    <x-icon name="file" />
                    <span class="nw-dropzone__text"><strong>{{ __('Choose a file') }}</strong> {{ __('or drag it here') }}</span>
                </label>
                <div class="nw-file" x-show="files.identification_document" x-cloak>
                    <x-icon name="check" />
                    <span class="nw-file__name" x-text="files.identification_document?.name"></span>
                    <small x-text="files.identification_document?.size"></small>
                    <button type="button" class="nw-link nw-link--small" x-on:click="remove('identification_document')">{{ __('Remove') }}</button>
                </div>
                <p class="nw-field__error" id="identification_document-error" role="alert" x-show="error('identification_document')" x-text="error('identification_document')" x-cloak></p>
            </div>

            <div class="nw-field nw-form-section__item" data-field="identity_issuing_country_code"
                data-type="dropdown" data-label="{{ __('Issuing country') }}" data-required>
                <label class="nw-label" for="identity_issuing_country_code">{{ __('Issuing country') }} <span class="nw-required" aria-hidden="true">*</span></label>
                <select class="nw-select" id="identity_issuing_country_code" name="identity_issuing_country_code" required
                    x-bind:aria-invalid="error('identity_issuing_country_code') ? 'true' : 'false'"
                    x-on:change="clear('identity_issuing_country_code')">
                    <option value="">{{ __('Select issuing country') }}</option>
                    @foreach ($identityCountryChoices as $country)
                        <option value="{{ $country['id'] }}" @selected(old('identity_issuing_country_code') === $country['id'])>{{ $country['body'] }}</option>
                    @endforeach
                </select>
                <p class="nw-field__error" role="alert" x-show="error('identity_issuing_country_code')" x-text="error('identity_issuing_country_code')" x-cloak></p>
            </div>
        @endif

        @if ($applicantProfile->resume_original_name && ! $profileRequirements['resume'])
            <div class="nw-field nw-form-section__item" data-field="resume" data-type="file" data-label="{{ __('Résumé / CV') }}" data-required>
                <span class="nw-label">{{ __('Résumé / CV') }} <span class="nw-required" aria-hidden="true">*</span></span>
                <p>{{ $applicantProfile->resume_original_name }}</p>
                <p class="nw-form-hint">{{ __('Your saved résumé will be copied into this application.') }}</p>
            </div>
        @else
            <div class="nw-form-section__item">
                <x-application-field :field="[
                    'name' => 'resume',
                    'label' => __('Résumé / CV'),
                    'type' => 'file',
                    'required' => true,
                    'accept' => ['pdf', 'doc', 'docx'],
                    'max_file_size' => 5 * 1024 * 1024,
                ]" />
            </div>
        @endif
    </div>
</fieldset>
