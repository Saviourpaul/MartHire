@if ($profileStepEnabled)
    <fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
        data-step-title="{{ __('Additional Info') }}"
        data-step-description="{{ __('Add the profile details this application is missing.') }}"
        x-show="!ready || step === 2" :aria-hidden="ready && step !== 2">
        <legend data-wizard-heading tabindex="-1">{{ __('Additional Info') }}</legend>


        @if (in_array(true, $profileRequirements['contact'], true))
            <section class="nw-application-profile-section">
                <h2>{{ __('Professional introduction and contact preferences') }}</h2>
                <div class="nw-form-section__grid">
                    @if ($profileRequirements['contact']['professional_headline'])
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_contact.professional_headline',
                            'input_name' => 'profile_contact[professional_headline]',
                            'label' => __('Professional headline'),
                            'type' => 'text',
                            'required' => true,
                            'min_length' => 2,
                            'max_length' => 160,
                        ]"
                                :value="old(
                                    'profile_contact.professional_headline',
                                    $applicantProfile->professional_headline,
                                )" /></div>
                    @endif
                    @if ($profileRequirements['contact']['about'])
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_contact.about',
                            'input_name' => 'profile_contact[about]',
                            'label' => __('About you'),
                            'type' => 'textarea',
                            'required' => true,
                            'min_length' => 10,
                            'max_length' => 3000,
                        ]"
                                :value="old('profile_contact.about', $applicantProfile->about)" /></div>
                    @endif
                    @if ($profileRequirements['contact']['disability_status'])
                        <div class="nw-field nw-form-section__item"
                            data-field="profile_contact.disability_status" data-type="boolean"
                            data-label="{{ __('Disability status') }}" data-required
                            data-error-key="profile_contact.disability_status">
                            <fieldset class="nw-fieldset"
                                aria-describedby="profile-disability-status-error">
                                <legend class="nw-label">{{ __('Disability status') }} <span
                                        class="nw-required" aria-hidden="true">*</span></legend>
                                <div class="nw-choices nw-choices--inline">
                                    <label class="nw-choice"><input type="radio"
                                            name="profile_contact[disability_status]"
                                            value="yes" x-model="profileDisabilityStatus"
                                            x-on:change="clear('profile_contact.disability_status')">
                                        <span>{{ __('Yes') }}</span></label>
                                    <label class="nw-choice"><input type="radio"
                                            name="profile_contact[disability_status]"
                                            value="no" x-model="profileDisabilityStatus"
                                            x-on:change="clear('profile_contact.disability_status')">
                                        <span>{{ __('No') }}</span></label>
                                </div>
                            </fieldset>
                            <p class="nw-field__error" id="profile-disability-status-error"
                                role="alert" x-show="error('profile_contact.disability_status')"
                                x-text="error('profile_contact.disability_status')" x-cloak></p>
                        </div>
                    @endif
                    @if ($profileRequirements['contact']['disability_details'] || $profileRequirements['contact']['disability_status'])
                        <div class="nw-form-section__item"
                            x-show="profileDisabilityStatus === 'yes'" x-cloak>
                            <div class="nw-field" data-field="profile_contact.disability_details"
                                data-type="text"
                                data-label="{{ __('Please specify your disability') }}"
                                :data-required="profileDisabilityStatus === 'yes' ? '' : null"
                                data-error-key="profile_contact.disability_details">
                                <label class="nw-label"
                                    for="profile-disability-details">{{ __('Please specify your disability') }}
                                    @if ($profileRequirements['contact']['disability_details'])
                                        <span class="nw-required"
                                        aria-hidden="true">*</span>@else<span
                                            class="nw-optional">{{ __('Optional') }}</span>
                                    @endif
                                </label>
                                <input id="profile-disability-details" class="nw-input"
                                    type="text" name="profile_contact[disability_details]"
                                    value="{{ old('profile_contact.disability_details', $applicantProfile->selfIdentification?->disability_details) }}"
                                    maxlength="500"
                                    :required="profileDisabilityStatus === 'yes'"
                                    x-on:input="clear('profile_contact.disability_details')"
                                    aria-describedby="profile-disability-details-error"
                                    :aria-invalid="error('profile_contact.disability_details') ? 'true' : 'false'">
                                <p class="nw-field__error" id="profile-disability-details-error"
                                    role="alert"
                                    x-show="error('profile_contact.disability_details')"
                                    x-text="error('profile_contact.disability_details')" x-cloak>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($profileRequirements['employment'] || $profileRequirements['skills'])
            <section class="nw-application-profile-section">
                <h2>{{ __('Employment and skills') }}</h2>
                <div class="nw-form-section__grid">
                    @if ($profileRequirements['employment'])
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.company',
                            'input_name' => 'profile_employment[company]',
                            'label' => __('Company'),
                            'type' => 'text',
                            'required' => true,
                            'min_length' => 2,
                            'max_length' => 255,
                        ]"
                                :value="old('profile_employment.company')" /></div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.title',
                            'input_name' => 'profile_employment[title]',
                            'label' => __('Job title'),
                            'type' => 'text',
                            'required' => true,
                            'min_length' => 2,
                            'max_length' => 255,
                        ]"
                                :value="old('profile_employment.title')" /></div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.start_date',
                            'input_name' => 'profile_employment[start_date]',
                            'label' => __('Start date'),
                            'type' => 'date',
                            'required' => true,
                            'max' => today()->toDateString(),
                        ]"
                                :value="old('profile_employment.start_date')" /></div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.end_date',
                            'input_name' => 'profile_employment[end_date]',
                            'label' => __('End date'),
                            'type' => 'date',
                            'required' => false,
                            'max' => today()->toDateString(),
                        ]"
                                :value="old('profile_employment.end_date')" /></div>
                        <div class="nw-field nw-form-section__item"
                            data-field="profile_employment.is_current" data-type="boolean"
                            data-label="{{ __('I currently work here') }}">
                            <input type="hidden" name="profile_employment[is_current]"
                                value="0">
                            <label class="nw-checkbox-label" for="profile-employment-current">
                                <input class="nw-checkbox" id="profile-employment-current"
                                    name="profile_employment[is_current]" type="checkbox"
                                    value="1" :checked="profileEmploymentCurrent"
                                    x-on:change="profileEmploymentCurrent = $event.target.checked">
                                <span>{{ __('I currently work here') }}</span>
                            </label>
                        </div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.location',
                            'input_name' => 'profile_employment[location]',
                            'label' => __('Work location'),
                            'type' => 'text',
                            'required' => false,
                            'max_length' => 255,
                        ]"
                                :value="old('profile_employment.location')" /></div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.employment_type',
                            'input_name' => 'profile_employment[employment_type]',
                            'label' => __('Employment type'),
                            'type' => 'dropdown',
                            'required' => false,
                            'choices' => $employmentTypeChoices,
                        ]"
                                :value="old('profile_employment.employment_type')" /></div>
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_employment.description',
                            'input_name' => 'profile_employment[description]',
                            'label' => __('What you did'),
                            'type' => 'textarea',
                            'required' => false,
                            'max_length' => 5000,
                        ]"
                                :value="old('profile_employment.description')" /></div>
                    @endif
                    @if ($profileRequirements['skills'])
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_skills',
                            'label' => __('Skills'),
                            'type' => 'text',
                            'required' => true,
                            'max_length' => 1000,
                            'hint' => __('Separate skills with commas.'),
                        ]"
                                :value="old('profile_skills')" /></div>
                    @endif
                </div>
            </section>
        @endif

        @if ($profileRequirements['education'])
            <section class="nw-application-profile-section">
                <h2>{{ __('Education history') }}</h2>
                <div class="nw-form-section__grid">
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_education.institution',
                        'input_name' => 'profile_education[institution]',
                        'label' => __('School or institution'),
                        'type' => 'text',
                        'required' => true,
                        'min_length' => 2,
                        'max_length' => 255,
                    ]"
                            :value="old('profile_education.institution')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_education.qualification',
                        'input_name' => 'profile_education[qualification]',
                        'label' => __('Degree'),
                        'type' => 'text',
                        'required' => true,
                        'min_length' => 2,
                        'max_length' => 255,
                    ]"
                            :value="old('profile_education.qualification')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_education.field_of_study',
                        'input_name' => 'profile_education[field_of_study]',
                        'label' => __('Field of study'),
                        'type' => 'text',
                        'required' => false,
                        'max_length' => 255,
                    ]"
                            :value="old('profile_education.field_of_study')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_education.start_date',
                        'input_name' => 'profile_education[start_date]',
                        'label' => __('Start date'),
                        'type' => 'date',
                        'required' => false,
                        'max' => today()->toDateString(),
                    ]"
                            :value="old('profile_education.start_date')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_education.end_date',
                        'input_name' => 'profile_education[end_date]',
                        'label' => __('End date'),
                        'type' => 'date',
                        'required' => false,
                        'max' => today()->toDateString(),
                    ]"
                            :value="old('profile_education.end_date')" /></div>
                  
            </section>
        @endif

        @if ($profileRequirements['online_profiles'])
            <section class="nw-application-profile-section">
                <h2>{{ __('Online profiles') }}</h2>
                <p class="nw-form-hint">
                    {{ __('Add at least one link. You can add LinkedIn, GitHub, or a personal website.') }}
                </p>
                <div class="nw-form-section__grid">
                    @foreach (['linkedin' => __('LinkedIn'), 'github' => __('GitHub'), 'website' => __('Website')] as $platform => $label)
                        <div class="nw-form-section__item"><x-application-field :field="[
                            'name' => 'profile_links.' . $platform,
                            'input_name' => 'profile_links[' . $platform . ']',
                            'label' => $label,
                            'type' => 'text',
                            'required' => false,
                            'max_length' => 2048,
                            'hint' => __('Enter a full URL, including https://.'),
                        ]"
                                :value="old('profile_links.' . $platform)" /></div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($profileRequirements['work_preferences'])
            <section class="nw-application-profile-section">
                <h2>{{ __('Work preferences') }}</h2>
                <p class="nw-form-hint">
                    {{ __('Add at least one preference to help match you with suitable roles.') }}
                </p>
                <div class="nw-form-section__grid">
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.preferred_roles',
                        'input_name' => 'profile_preferences[preferred_roles]',
                        'label' => __('Preferred roles'),
                        'type' => 'text',
                        'required' => false,
                        'max_length' => 1000,
                        'hint' => __('Separate roles with commas.'),
                    ]"
                            :value="old('profile_preferences.preferred_roles')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.preferred_locations',
                        'input_name' => 'profile_preferences[preferred_locations]',
                        'label' => __('Preferred locations'),
                        'type' => 'text',
                        'required' => false,
                        'max_length' => 1000,
                        'hint' => __('Separate locations with commas.'),
                    ]"
                            :value="old('profile_preferences.preferred_locations')" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.preferred_job_type',
                        'input_name' => 'profile_preferences[preferred_job_type]',
                        'label' => __('Preferred employment type'),
                        'type' => 'dropdown',
                        'required' => false,
                        'choices' => $employmentTypeChoices,
                    ]"
                            :value="old(
                                'profile_preferences.preferred_job_type',
                                $jobPreferences->preferred_job_type,
                            )" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.preferred_workplace',
                        'input_name' => 'profile_preferences[preferred_workplace]',
                        'label' => __('Workplace'),
                        'type' => 'dropdown',
                        'required' => false,
                        'choices' => [
                            ['id' => 'on_site', 'body' => __('On-site')],
                            ['id' => 'hybrid', 'body' => __('Hybrid')],
                            ['id' => 'remote', 'body' => __('Remote')],
                        ],
                    ]"
                            :value="old(
                                'profile_preferences.preferred_workplace',
                                $jobPreferences->preferred_workplace,
                            )" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.salary_min',
                        'input_name' => 'profile_preferences[salary_min]',
                        'label' => __('Minimum salary'),
                        'type' => 'numeric',
                        'required' => false,
                        'max' => 100000000,
                    ]"
                            :value="old(
                                'profile_preferences.salary_min',
                                $jobPreferences->salary_min,
                            )" /></div>
                    <div class="nw-form-section__item"><x-application-field :field="[
                        'name' => 'profile_preferences.salary_max',
                        'input_name' => 'profile_preferences[salary_max]',
                        'label' => __('Maximum salary'),
                        'type' => 'numeric',
                        'required' => false,
                        'max' => 100000000,
                    ]"
                            :value="old(
                                'profile_preferences.salary_max',
                                $jobPreferences->salary_max,
                            )" /></div>
                </div>
            </section>
        @endif
    </fieldset>
@endif
