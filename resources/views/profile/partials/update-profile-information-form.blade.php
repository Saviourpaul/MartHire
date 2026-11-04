@php
    $profileCountryCode = old('country_code', $user->country_code ?? '');
    $profileState = old('state', $user->state ?? '');
    $profileCity = old('city', $user->city ?? '');
    $profileStateOptions =
        $states ??
        (is_string($profileState) && $profileState !== ''
            ? [['value' => $profileState, 'label' => $profileState]]
            : []);
    $profileCityOptions =
        $cities ??
        (is_string($profileCity) && $profileCity !== '' ? [['value' => $profileCity, 'label' => $profileCity]] : []);
    $profileCountryName = collect($countries)->firstWhere('value', $profileCountryCode)['label'] ?? '';
    $savedContactIdentity = $user->isApplicant() ? $applicantProfile->selfIdentification : null;
    $genderIdentity = old('gender_identity', $savedContactIdentity?->gender_identity);
    $disabilityStatus = old('disability_status', $savedContactIdentity?->disability_status);
    $disabilityDetails = old('disability_details', $savedContactIdentity?->disability_details);
@endphp

<section aria-labelledby="profile-information-heading">
    <header class="nw-panel-heading">
        <h2 id="profile-information-heading">{{ __('Personal details') }}</h2>
        <p>{{ __('Update your photo and personal details.') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="nw-form-stack" enctype="multipart/form-data"
        data-applicant-profile-async-form x-data="locationSelector({
            countries: @js($countries),
            countryCode: @js($profileCountryCode),
            state: @js($profileState),
            city: @js($profileCity),
            preloadedStates: @js($profileStateOptions),
            preloadedCities: @js($profileCityOptions),
            statesUrl: @js(route('locations.states')),
            citiesUrl: @js(route('locations.cities')),
            nigeriaUsesLga: false,
            statesErrorMessage: @js(__('We could not load the states. Please try again.')),
            citiesErrorMessage: @js(__('We could not load the cities. Please try again.')),
        })">
        @csrf
        @method('patch')

        <x-avatar-upload :user="$user" :required="$user->isApplicant() && blank($user->profile_image_path)" :types="['jpg', 'jpeg', 'png', 'webp', 'gif']" :max-bytes="2097152" :hint="__('JPG, PNG, WEBP or GIF. Up to 2 MB.')" />

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $user->first_name)" required autofocus
                    autocomplete="given-name" />
                <x-input-error id="first_name-error" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $user->last_name)" required
                    autocomplete="family-name" />
                <x-input-error id="last_name-error" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" name="email" type="email" :value="$user->email" readonly
                autocomplete="username" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div class="nw-notice">
                    <p>{{ __('Your email address is unverified.') }}</p>
                    <button type="submit" form="send-verification" class="nw-link">
                        {{ __('Resend verification email') }}
                    </button>
                </div>
            @endif
        </div>

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="phone" :value="__('Phone number')" />
                <x-phone-input id="phone" name="phone" :value="old('phone', $user->phone)" :required="$user->isApplicant()" />
            </div>
            <div>
                <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" />
                <x-input-error id="date_of_birth-error" :messages="$errors->get('date_of_birth')" />
            </div>
        </div>

        <div>
            <x-input-label for="address" :value="__('Address')" />
            <x-text-input id="address" name="address" type="text" :value="old('address', $user->address)"
                autocomplete="street-address" />
            <x-input-error id="address-error" :messages="$errors->get('address')" />
        </div>

        @if ($user->isApplicant())
            <div class="nw-field">
                <x-input-label for="professional_headline" :value="__('Professional headline')" />
                <x-text-input id="professional_headline" name="professional_headline" type="text" maxlength="160"
                    :value="old('professional_headline', $applicantProfile->professional_headline)" :placeholder="__('e.g. Product designer focused on accessible digital services')" />
                <x-input-error id="professional_headline-error" :messages="$errors->get('professional_headline')" />
            </div>
            <div class="nw-field">
                <x-input-label for="about" :value="__('About you')" />
                <textarea id="about" class="nw-input" name="about" rows="5" maxlength="3000">{{ old('about', $applicantProfile->about) }}</textarea>
                <p class="nw-hint">{{ __('Share a concise summary of your experience and strengths.') }}</p>
                <x-input-error id="about-error" :messages="$errors->get('about')" />
            </div>
        @endif

        @if ($user->isApplicant())
            <div x-data="{ disabilityStatus: @js($disabilityStatus), disabilityDetails: @js($disabilityDetails) }">
                <div class="nw-form-row nw-form-row--fields">
                    <div class="nw-field">
                        <x-input-label for="gender_identity" :value="__('Gender identity')" />
                        <select id="gender_identity" class="nw-select" name="gender_identity">
                            <option value="">{{ __('Choose an option') }}</option>
                            <option value="male" @selected($genderIdentity === 'male')>{{ __('Male') }}</option>
                            <option value="female" @selected($genderIdentity === 'female')>{{ __('Female') }}</option>
                        </select>
                        <x-input-error id="gender_identity-error" :messages="$errors->get('gender_identity')" />
                    </div>
                    <div class="nw-field">
                        <x-input-label for="disability_status" :value="__('Disability status')" />
                        <select id="disability_status" class="nw-select" name="disability_status"
                            x-model="disabilityStatus"
                            x-on:change="if (disabilityStatus !== 'yes') disabilityDetails = ''">
                            <option value="">{{ __('Choose an option') }}</option>
                            <option value="yes">{{ __('Yes') }}</option>
                            <option value="no">{{ __('No') }}</option>
                        </select>
                        <x-input-error id="disability_status-error" :messages="$errors->get('disability_status')" />
                    </div>
                </div>
                <div class="nw-field" x-show="disabilityStatus === 'yes'" x-cloak>
                    <x-input-label for="disability_details" :value="__('Please specify your disability')" />
                    <textarea id="disability_details" class="nw-input" name="disability_details" rows="3" maxlength="1000"
                        x-model="disabilityDetails" x-bind:required="disabilityStatus === 'yes'"
                        x-bind:disabled="disabilityStatus !== 'yes'"></textarea>
                    <x-input-error id="disability_details-error" :messages="$errors->get('disability_details')" />
                </div>
            </div>
        @endif

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="country_code" :value="__('Country')" />
                <select id="country_code" class="nw-select" name="country_code" x-model="countryCode"
                    x-on:change="loadStates()">
                    <option value="">{{ __('Select a country') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country['value'] }}" @selected($profileCountryCode === $country['value'])>{{ $country['label'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="country" value="{{ $profileCountryName }}" :value="countryName()">
                <x-input-error id="country_code-error" :messages="$errors->get('country_code')" />
            </div>
            <div>
                <x-input-label for="state" x-bind:for="stateManual ? 'state-manual' : 'state'" :value="__('State')" />
                <select id="state" class="nw-select" name="state" x-model="state" x-on:change="loadCities()"
                    x-show="!stateManual" x-bind:disabled="stateManual || !countryCode || loadingStates">
                    <option value=""
                        x-text="loadingStates ? @js(__('Loading…')) : @js(__('Select a state'))">
                    </option>
                    <template x-for="option in states" :key="option.value">
                        <option :value="option.value" x-text="option.label" x-bind:selected="option.value === state">
                        </option>
                    </template>
                </select>
                <input id="state-manual" name="state" type="text" class="nw-input" maxlength="255"
                    autocomplete="address-level1" disabled x-model="state" x-on:change="loadCities()"
                    x-show="stateManual" x-bind:disabled="!stateManual || !countryCode || loadingStates" x-cloak>
                <x-input-error id="state-error" :messages="$errors->get('state')" />
            </div>
        </div>

        <div>
            <x-input-label for="city" x-bind:for="cityManual ? 'city-manual' : 'city'" :value="__('City or Town')" />
            <select id="city" class="nw-select" name="city" x-model="city" x-show="!cityManual"
                x-bind:disabled="cityManual || !state || loadingCities">
                <option value="">{{ __('Select a city') }}</option>
                <template x-for="option in cities" :key="option.value">
                    <option :value="option.value" x-text="option.label" x-bind:selected="option.value === city">
                    </option>
                </template>
            </select>
            <input id="city-manual" name="city" type="text" class="nw-input" maxlength="255"
                autocomplete="address-level2" disabled x-model="city" x-show="cityManual"
                x-bind:disabled="!cityManual || !state || loadingCities" x-cloak>
            <x-input-error id="city-error" :messages="$errors->get('city')" />
        </div>

        <div class="nw-notice nw-notice--error" role="alert" x-show="locationError" x-cloak>
            <p x-text="locationError"></p>
            <button type="button" class="nw-link" x-show="statesError" x-on:click="loadStates(true)"
                x-bind:disabled="loadingStates">{{ __('Retry states') }}</button>
            <button type="button" class="nw-link" x-show="citiesError" x-on:click="loadCities(true)"
                x-bind:disabled="loadingCities || !state">{{ __('Retry cities') }}</button>
        </div>

        <x-form.actions>
            <x-primary-button
                x-bind:disabled="loadingStates || loadingCities">{{ __('Save changes') }}</x-primary-button>
        </x-form.actions>
    </form>
</section>
