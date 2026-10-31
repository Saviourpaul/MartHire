@php
    $profileCountryCode = old('country_code', $user->country_code ?? '');
    $profileState = old('state', $user->state ?? '');
    $profileCity = old('city', $user->city ?? '');
    $profileStateOptions = $states ?? (is_string($profileState) && $profileState !== '' ? [['value' => $profileState, 'label' => $profileState]] : []);
    $profileCityOptions = $cities ?? (is_string($profileCity) && $profileCity !== '' ? [['value' => $profileCity, 'label' => $profileCity]] : []);
    $profileCountryName = collect($countries)->firstWhere('value', $profileCountryCode)['label'] ?? '';
@endphp

<section aria-labelledby="profile-information-heading">
    <header class="nw-panel-heading">
        <span class="nw-eyebrow">{{ __('The essentials') }}</span>
        <h2 id="profile-information-heading">{{ __('Personal details') }}</h2>
        <p>{{ __('Update your photo and personal details.') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="nw-form-stack" enctype="multipart/form-data"
        x-data="locationSelector({
            countries: @js($countries),
            countryCode: @js($profileCountryCode), state: @js($profileState), city: @js($profileCity),
            preloadedStates: @js($profileStateOptions), preloadedCities: @js($profileCityOptions),
            statesUrl: @js(route('locations.states')), citiesUrl: @js(route('locations.cities')),
            nigeriaUsesLga: false,
            statesErrorMessage: @js(__('We could not load the states. Please try again.')),
            citiesErrorMessage: @js(__('We could not load the cities. Please try again.')),
        })">
        @csrf
        @method('patch')

        <x-avatar-upload :user="$user" :required="$user->isApplicant() && blank($user->profile_image_path)"
            :types="['jpg', 'jpeg', 'png', 'webp', 'gif']" :max-bytes="2097152"
            :hint="__('JPG, PNG, WEBP or GIF. Up to 2 MB.')" />

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error id="first_name-error" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error id="last_name-error" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" name="email" type="email" :value="$user->email" readonly autocomplete="username" />
            <p class="nw-form-hint">{{ __('Your email address cannot be changed from your profile.') }}</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
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
            <x-text-input id="address" name="address" type="text" :value="old('address', $user->address)" autocomplete="street-address" />
            <x-input-error id="address-error" :messages="$errors->get('address')" />
        </div>

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="country_code" :value="__('Country')" />
                <select id="country_code" class="nw-select" name="country_code" x-model="countryCode" x-on:change="loadStates()">
                    <option value="">{{ __('Select a country') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country['value'] }}" @selected($profileCountryCode === $country['value'])>{{ $country['label'] }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="country" value="{{ $profileCountryName }}" :value="countryName()">
                <x-input-error id="country_code-error" :messages="$errors->get('country_code')" />
            </div>
            <div>
                <x-input-label for="state" x-bind:for="stateManual ? 'state-manual' : 'state'" :value="__('State')" />
                <select id="state" class="nw-select" name="state" x-model="state" x-on:change="loadCities()" x-show="!stateManual" x-bind:disabled="stateManual || !countryCode || loadingStates">
                    <option value="" x-text="loadingStates ? @js(__('Loading…')) : @js(__('Select a state'))"></option>
                    <template x-for="option in states" :key="option.value">
                        <option :value="option.value" x-text="option.label" x-bind:selected="option.value === state"></option>
                    </template>
                </select>
                <input id="state-manual" name="state" type="text" class="nw-input" maxlength="255" autocomplete="address-level1" disabled
                    x-model="state" x-on:change="loadCities()" x-show="stateManual" x-bind:disabled="!stateManual || !countryCode || loadingStates" x-cloak>
                <x-input-error id="state-error" :messages="$errors->get('state')" />
            </div>
        </div>

        <div>
            <x-input-label for="city" x-bind:for="cityManual ? 'city-manual' : 'city'" :value="__('City / Local government area')" />
            <select id="city" class="nw-select" name="city" x-model="city" x-show="!cityManual" x-bind:disabled="cityManual || !state || loadingCities">
                <option value="">{{ __('Select a city') }}</option>
                <template x-for="option in cities" :key="option.value">
                    <option :value="option.value" x-text="option.label" x-bind:selected="option.value === city"></option>
                </template>
            </select>
            <input id="city-manual" name="city" type="text" class="nw-input" maxlength="255" autocomplete="address-level2" disabled
                x-model="city" x-show="cityManual" x-bind:disabled="!cityManual || !state || loadingCities" x-cloak>
            <x-input-error id="city-error" :messages="$errors->get('city')" />
        </div>

        <div class="nw-notice nw-notice--error" role="alert" x-show="locationError" x-cloak>
            <p x-text="locationError"></p>
            <button type="button" class="nw-link" x-show="statesError" x-on:click="loadStates(true)" x-bind:disabled="loadingStates">{{ __('Retry states') }}</button>
            <button type="button" class="nw-link" x-show="citiesError" x-on:click="loadCities(true)" x-bind:disabled="loadingCities || !state">{{ __('Retry cities') }}</button>
        </div>

        <x-form.actions>
            <x-primary-button x-bind:disabled="loadingStates || loadingCities">{{ __('Save changes') }}</x-primary-button>
        </x-form.actions>
    </form>
</section>
