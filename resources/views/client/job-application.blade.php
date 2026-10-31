@php
    $initialCountry = old('country_code', $user->country_code ?? '');
    $initialState = old('state', $user->state ?? '');
    $initialCity = old('city', $user->city ?? '');
    $initialLga = old('local_government_area', $user->local_government_area ?? '');
    $education = collect(old('education_documents', [['name' => '']]))
        ->take(10)
        ->values()
        ->map(
            fn($document, $index) => [
                'key' => $index,
                'name' => is_array($document) && is_string($document['name'] ?? null) ? $document['name'] : '',
            ],
        );
    $serverErrors = collect($errors->messages())->map(fn($messages) => $messages[0])->all();
    $personalFields = [
        [
            'name' => 'first_name',
            'label' => __('First name'),
            'type' => 'text',
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'autocomplete' => 'given-name',
        ],
        [
            'name' => 'middle_name',
            'label' => __('Middle name'),
            'type' => 'text',
            'required' => false,
            'max_length' => 100,
            'autocomplete' => 'additional-name',
        ],
        [
            'name' => 'last_name',
            'label' => __('Last name'),
            'type' => 'text',
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'autocomplete' => 'family-name',
        ],
        [
            'name' => 'email',
            'label' => __('Email address'),
            'type' => 'email',
            'required' => true,
            'max_length' => 255,
            'autocomplete' => 'email',
        ],
        [
            'name' => 'phone',
            'label' => __('Phone number'),
            'type' => 'tel',
            'required' => true,
            'autocomplete' => 'tel',
        ],
        [
            'name' => 'date_of_birth',
            'label' => __('Date of birth'),
            'type' => 'date',
            'required' => true,
            'min' => '1900-01-01',
            'max' => today()->subDay()->toDateString(),
        ],
        [
            'name' => 'gender',
            'label' => __('Gender'),
            'type' => 'dropdown',
            'required' => true,
            'choices' => [
                ['id' => 'male', 'body' => __('Male')],
                ['id' => 'female', 'body' => __('Female')],
                ['id' => 'other', 'body' => __('Other')],
            ],
        ],
        [
            'name' => 'marital_status',
            'label' => __('Marital status'),
            'type' => 'dropdown',
            'required' => true,
            'choices' => [
                ['id' => 'single', 'body' => __('Single')],
                ['id' => 'married', 'body' => __('Married')],
                ['id' => 'Other', 'body' => __('Other')],
            ],
        ],
    ];
    $identityField = [
        'name' => 'identification_type',
        'label' => __('Identification method'),
        'type' => 'dropdown',
        'required' => true,
        'choices' => array_map(
            fn($type) => ['id' => $type->value, 'body' => $type->label()],
            \App\Enums\ApplicationDocumentType::identityTypes(),
        ),
    ];
@endphp

<x-layout :title="__('Apply for :title', ['title' => $job->title])">
    <section class="nw-section">
        <div class="nw-container nw-native-apply">
            <nav class="nw-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('jobs.show', $job) }}"><x-icon name="arrow-left" /> {{ __('Back to the role') }}</a>
            </nav>
            <header class="nw-section-heading">
                <div><span class="nw-eyebrow">{{ $job->company }}</span>
                    <h1 class="nw-heading">{{ __('Apply for :title', ['title' => $job->title]) }}</h1>
                </div>
                <p>{{ __('A few focused steps to introduce yourself and share your documents.') }}</p>
            </header>
            <p class="nw-job__meta"><x-job-country :job="$publicJob" /></p>

            <div class="nw-wizard" x-data="nativeApplicationForm({
                errors: @js($serverErrors),
                documents: @js($education),
                location: {
                    countries: @js($countries),
                    countryCode: @js($initialCountry),
                    state: @js($initialState),
                    city: @js($initialCity),
                    lga: @js($initialLga),
                    states: @js($states),
                    cities: @js($cities),
                    statesUrl: @js(route('locations.states')),
                    citiesUrl: @js(route('locations.cities')),
                    nigeriaUsesLga: true
                }
            })">
                <x-form.wizard-progress />
                <div class="nw-error-summary" x-ref="summary" tabindex="-1" role="alert" x-show="summary.length"
                    x-cloak>
                    <h2>{{ __('Please check these details') }}</h2>
                    <ul><template x-for="item in summary" :key="item.name">
                            <li><button type="button" class="nw-link" x-on:click="goTo(item)"><span
                                        x-text="item.label"></span>: <span x-text="item.message"></span></button></li>
                        </template></ul>
                </div>
                <div class="nw-error-summary" x-ref="submissionError" tabindex="-1" role="alert"
                    x-show="submissionError" x-text="submissionError" x-cloak></div>
                @if ($errors->any())
                    <noscript>
                        <div class="nw-notice nw-notice--error" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <p>{{ __('Please select your files again after reloading the page.') }}</p>
                        </div>
                    </noscript>
                @endif

                <form method="POST" action="{{ route('applications.store', $job) }}" enctype="multipart/form-data"
                    class="nw-wizard__form" novalidate x-ref="form" x-on:submit="submit($event)"
                    x-on:input="handleInput($event)" x-on:change="handleInput($event)">
                    @csrf
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
                                    <x-application-field :field="$field" :value="old($field['name'], $field['name'] === 'date_of_birth' ? $user->date_of_birth?->format('Y-m-d') : $user->getAttribute($field['name']))" />
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
                        data-step-title="{{ __('Your address') }}"
                        data-step-description="{{ __('Choose your country, state, and city or local government area.') }}"
                        x-show="!ready || step === 1" :aria-hidden="ready && step !== 1">
                        <legend data-wizard-heading tabindex="-1">{{ __('Where are you based?') }}</legend>
                        <p class="nw-wizard__description">
                            {{ __('Location options follow your selected country. Nigerian addresses use a local government area.') }}
                        </p>
                        <div class="nw-form-section__grid">
                            <div class="nw-field nw-form-section__item is-half" data-field="country_code"
                                data-type="dropdown" data-label="{{ __('Country') }}" data-required
                                data-location-field>
                                <label class="nw-label" for="country_code">{{ __('Country') }} <span
                                        class="nw-required">*</span></label>
                                <select id="country_code" name="country_code" class="nw-select" x-model="countryCode"
                                    x-on:change="loadStates()" required aria-describedby="country-error"
                                    :aria-invalid="error('country_code') ? 'true' : 'false'">
                                    <option value="">{{ __('Select a country') }}</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country['value'] }}" @selected($initialCountry === $country['value'])>
                                            {{ $country['label'] }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="country" :value="countryName()" />
                                <p class="nw-field__error" id="country-error" role="alert"
                                    x-show="error('country_code')" x-text="error('country_code')" x-cloak></p>
                            </div>
                            <div class="nw-field nw-form-section__item is-half" data-field="state" data-type="dropdown"
                                data-label="{{ __('State') }}" data-required data-location-field>
                                <label class="nw-label"
                                    :for="stateManual ? 'manual-state' : 'state'">{{ __('State / region') }} <span
                                        class="nw-required">*</span></label>
                                <select id="state" name="state" class="nw-select" x-model="state"
                                    x-on:change="loadCities()" x-show="!stateManual"
                                    :disabled="stateManual || !countryCode || loadingStates" required
                                    aria-describedby="state-hint state-error"
                                    :aria-invalid="error('state') ? 'true' : 'false'">
                                    <option value=""
                                        x-text="loadingStates ? @js(__('Loading states…')) : @js(__('Select a state'))">
                                    </option>
                                    <template x-for="option in states" :key="option.value">
                                        <option :value="option.value" x-text="option.label"
                                            :selected="option.value === state"></option>
                                    </template>
                                </select>
                                <input id="manual-state" name="state" class="nw-input" type="text" x-model="state"
                                    x-on:change="loadCities()" x-show="stateManual"
                                    :disabled="!stateManual || loadingStates" required minlength="2" maxlength="255"
                                    aria-label="{{ __('State / region') }}"
                                    aria-describedby="state-hint state-error" />
                                <p class="nw-form-hint" id="state-hint" x-show="stateManual">
                                    {{ __('Regions are not listed for this country. Enter your state or region.') }}
                                </p>
                                <p class="nw-field__error" id="state-error" role="alert" x-show="error('state')"
                                    x-text="error('state')" x-cloak></p>
                            </div>
                            <div class="nw-field nw-form-section__item" data-field="city" data-type="dropdown"
                                data-label="{{ __('City') }}"
                                :data-required="countryCode !== 'NG' ? '' : null" data-location-field
                                x-show="countryCode !== 'NG'">
                                <label class="nw-label"
                                    :for="cityManual ? 'manual-city' : 'city'">{{ __('City') }} <span
                                        class="nw-required">*</span></label>
                                <select id="city" name="city" class="nw-select" x-model="city"
                                    x-show="!cityManual" :required="countryCode !== 'NG'"
                                    :disabled="countryCode === 'NG' || cityManual || !state || loadingStates ||
                                        loadingCities"
                                    aria-describedby="city-hint city-error"
                                    :aria-invalid="error('city') ? 'true' : 'false'">
                                    <option value=""
                                        x-text="loadingCities ? @js(__('Loading cities…')) : @js(__('Select a city'))">
                                    </option>
                                    <template x-for="option in cities" :key="option.value">
                                        <option :value="option.value" x-text="option.label"
                                            :selected="option.value === city"></option>
                                    </template>
                                </select>
                                <input id="manual-city" name="city" class="nw-input" type="text"
                                    x-model="city" x-show="cityManual" :required="countryCode !== 'NG'"
                                    :disabled="countryCode === 'NG' || !cityManual || !state || loadingStates ||
                                        loadingCities"
                                    minlength="2" maxlength="255" aria-label="{{ __('City') }}"
                                    aria-describedby="city-hint city-error" />
                                <p class="nw-form-hint" id="city-hint" x-show="cityManual">
                                    {{ __('Cities are not listed for this state. Enter your city.') }}</p>
                                <p class="nw-field__error" id="city-error" role="alert" x-show="error('city')"
                                    x-text="error('city')" x-cloak></p>
                            </div>
                            <div class="nw-field nw-form-section__item" data-field="local_government_area"
                                data-type="dropdown" data-label="{{ __('Local government area') }}"
                                :data-required="countryCode === 'NG' ? '' : null" data-location-field
                                x-show="countryCode === 'NG'">
                                <label class="nw-label" for="local_government_area">{{ __('Local government area') }}
                                    <span class="nw-required">*</span></label>
                                <select id="local_government_area" name="local_government_area" class="nw-select"
                                    x-model="lga" :required="countryCode === 'NG'"
                                    :disabled="countryCode !== 'NG' || !state || loadingStates || loadingCities"
                                    aria-describedby="lga-error"
                                    :aria-invalid="error('local_government_area') ? 'true' : 'false'">
                                    <option value=""
                                        x-text="loadingCities ? @js(__('Loading areas…')) : @js(__('Select a local government area'))">
                                    </option>
                                    <template x-for="option in cities" :key="option.value">
                                        <option :value="option.value" x-text="option.label"
                                            :selected="option.value === lga"></option>
                                    </template>
                                </select>
                                <p class="nw-field__error" id="lga-error" role="alert"
                                    x-show="error('local_government_area')" x-text="error('local_government_area')"
                                    x-cloak></p>
                            </div>
                            <div class="nw-form-section__item nw-location-feedback" role="status"
                                aria-live="polite">
                                <p x-show="loadingStates || loadingCities" x-cloak>
                                    {{ __('Loading location options…') }}</p>
                                <p x-show="statesError" x-cloak><span x-text="statesError"></span> <button
                                        type="button" class="nw-link" x-on:click="loadStates(true)"
                                        :disabled="loadingStates">{{ __('Retry states') }}</button></p>
                                <p x-show="citiesError" x-cloak><span x-text="citiesError"></span> <button
                                        type="button" class="nw-link" x-on:click="loadCities(true)"
                                        :disabled="loadingCities">{{ __('Retry cities / areas') }}</button></p>
                            </div>
                            <div class="nw-form-section__item"><x-application-field :field="[
                                'name' => 'address',
                                'label' => __('Street address'),
                                'type' => 'text',
                                'required' => true,
                                'min_length' => 5,
                                'max_length' => 255,
                                'autocomplete' => 'street-address',
                            ]"
                                    :value="old('address', $user->address)" /></div>
                        </div>
                    </fieldset>

                    <fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
                        data-step-title="{{ __('Documents') }}"
                        data-step-description="{{ __('Add your identity document, CV, and qualifications.') }}"
                        x-show="!ready || step === 2" :aria-hidden="ready && step !== 2">
                        <legend data-wizard-heading tabindex="-1">{{ __('Share your documents') }}</legend>
                        <p class="nw-wizard__description">
                            {{ __('Files are stored privately for the hiring team. Each document can be up to 5 MB.') }}
                        </p>
                        <div class="nw-form-section__grid">
                            <div class="nw-form-section__item"><x-application-field :field="$identityField"
                                    :value="old('identification_type')" /></div>
                            <div class="nw-form-section__item"><x-application-field :field="[
                                'name' => 'identification_document',
                                'label' => __('Identification document'),
                                'type' => 'file',
                                'required' => true,
                                'accept' => ['pdf', 'jpg', 'jpeg', 'png'],
                                'max_file_size' => 5 * 1024 * 1024,
                            ]" /></div>
                            <div class="nw-form-section__item"><x-application-field :field="[
                                'name' => 'resume',
                                'label' => __('Résumé / CV'),
                                'type' => 'file',
                                'required' => true,
                                'accept' => ['pdf', 'doc', 'docx'],
                                'max_file_size' => 5 * 1024 * 1024,
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

                    <fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
                        data-step-title="{{ __('Review and submit') }}"
                        data-step-description="{{ __('Check your application before sending it.') }}"
                        x-show="!ready || step === 3" :aria-hidden="ready && step !== 3">
                        <legend data-wizard-heading tabindex="-1">{{ __('Ready to send?') }}</legend>
                        <p class="nw-wizard__description">
                            {{ __('Review your details and documents. Use Edit to make any changes before submitting.') }}
                        </p>
                        <x-form.wizard-review />
                        <p class="nw-form-hint">
                            {{ __('Your application and documents will be shared with the organization reviewing this role.') }}
                        </p>
                    </fieldset>
                    <x-form.wizard-actions :cancel-url="route('jobs.show', $job)" :submit-label="__('Submit application')" />
                </form>
            </div>
        </div>
    </section>
</x-layout>
