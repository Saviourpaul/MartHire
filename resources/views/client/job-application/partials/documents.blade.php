<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Documents') }}"
    data-step-description="{{ __('Add your identity document, CV, and qualifications.') }}"
    x-show="!ready || step === {{ $profileStepEnabled ? 3 : 2 }}"
    :aria-hidden="ready && step !== {{ $profileStepEnabled ? 3 : 2 }}">
    <legend data-wizard-heading tabindex="-1">{{ __('Share your documents') }}</legend>
    <p class="nw-wizard__description">
        {{ __('Files are stored privately for the hiring team. Each document can be up to 5 MB.') }}
    </p>
    <div class="nw-form-section__grid">
        @if ($savedIdentityDocument)
            <p class="nw-form-hint nw-form-section__item">
                {{ __('Using your saved :type document (:name). Upload a replacement below only if you want to change it.', ['type' => $savedIdentityDocument->document_type->label(), 'name' => $savedIdentityDocument->original_name]) }}
            </p>
        @endif
        <div class="nw-form-section__item"><x-application-field :field="$identityField"
                :value="old(
                    'identification_type',
                    $savedIdentityDocument?->document_type->value,
                )" /></div>
        <div class="nw-form-section__item"><x-application-field :field="[
            'name' => 'identification_document',
            'label' => $savedIdentityDocument
                ? __('Replace identification document')
                : __('Identification document'),
            'type' => 'file',
            'required' => $profileRequirements['identification'],
            'accept' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_file_size' => 5 * 1024 * 1024,
            'hint' => $savedIdentityDocument
                ? __(
                    'Optional. Your saved document will be used if you do not select a replacement.',
                )
                : null,
        ]" /></div>
        @if ($applicantProfile->resume_original_name && !$profileRequirements['resume'])
            <p class="nw-form-hint nw-form-section__item">
                {{ __('Using your saved résumé: :name. Upload a replacement below only if you want to change it.', ['name' => $applicantProfile->resume_original_name]) }}
            </p>
        @endif
        <div class="nw-form-section__item"><x-application-field :field="[
            'name' => 'resume',
            'label' => !$profileRequirements['resume']
                ? __('Replace résumé / CV')
                : __('Résumé / CV'),
            'type' => 'file',
            'required' => $profileRequirements['resume'],
            'accept' => ['pdf', 'doc', 'docx'],
            'max_file_size' => 5 * 1024 * 1024,
            'hint' => !$profileRequirements['resume']
                ? __(
                    'Optional. Your saved résumé will be included if you do not select a replacement.',
                )
                : null,
        ]" /></div>
    </div>
    <div class="nw-form-stack nw-wizard__qualifications">
        <template x-for="(document, index) in documents" :key="document.key">
            <div class="nw-wizard__qualification">
                <div class="nw-field" :data-field="'education_documents.' + index + '.name'"
                    data-type="text" data-label="{{ __('Qualification title') }}" data-required>
                    <label class="nw-label"
                        :for="'education-name-' + index">{{ __('Educational qualification title') }}
                        <span class="nw-required">*</span></label>
                    <input class="nw-input" :id="'education-name-' + index"
                        :name="'education_documents[' + index + '][name]'" x-model="document.name"
                        required minlength="2" maxlength="100"
                        :aria-describedby="'education-name-error-' + index"
                        :aria-invalid="error('education_documents.' + index + '.name') ? 'true' : 'false'" />
                    <p class="nw-field__error" :id="'education-name-error-' + index"
                        role="alert" x-show="error('education_documents.' + index + '.name')"
                        x-text="error('education_documents.' + index + '.name')" x-cloak></p>
                </div>
                <div class="nw-field" :data-field="'education_documents.' + index + '.file'"
                    data-type="file" data-label="{{ __('Qualification document') }}"
                    data-required data-accept="pdf,jpg,jpeg,png" data-max="5242880">
                    <label class="nw-label"
                        :for="'education-file-' + index">{{ __('Qualification document') }} <span
                            class="nw-required">*</span></label>
                    <input class="nw-input" :id="'education-file-' + index"
                        :name="'education_documents[' + index + '][file]'" type="file"
                        accept=".pdf,.jpg,.jpeg,.png" required x-on:change="pick($event)"
                        :aria-describedby="'education-file-error-' + index"
                        :aria-invalid="error('education_documents.' + index + '.file') ? 'true' : 'false'" />
                    <p class="nw-field__error" :id="'education-file-error-' + index"
                        role="alert" x-show="error('education_documents.' + index + '.file')"
                        x-text="error('education_documents.' + index + '.file')" x-cloak></p>
                </div>
                <button class="nw-link nw-link--small" type="button"
                    x-show="documents.length > 1"
                    x-on:click="removeDocument(index)">{{ __('Remove qualification') }}</button>
            </div>
        </template>
        <button type="button" class="nw-button nw-button--outline" x-on:click="addDocument()"
            :disabled="documents.length >= 10">{{ __('Add another qualification') }}</button>
    </div>
</fieldset>
