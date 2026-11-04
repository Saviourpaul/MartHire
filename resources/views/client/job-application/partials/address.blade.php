<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step data-step-title="{{ __('Your address') }}"
    data-step-description="{{ __('Choose your country, state, and city or local government area.') }}"
    x-show="!ready || step === 1" :aria-hidden="ready && step !== 1">
    <legend data-wizard-heading tabindex="-1">{{ __('Where are you located?') }}</legend>

    <div class="nw-form-section__grid">
        <div class="nw-field nw-form-section__item is-half" data-field="country_code" data-type="dropdown"
            data-label="{{ __('Country') }}" data-required data-location-field>
            <label class="nw-label" for="country_code">{{ __('Country') }} <span class="nw-required">*</span></label>
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
            <p class="nw-field__error" id="country-error" role="alert" x-show="error('country_code')"
                x-text="error('country_code')" x-cloak></p>
        </div>
        <div class="nw-field nw-form-section__item is-half" data-field="state" data-type="dropdown"
            data-label="{{ __('State') }}" data-required data-location-field>
            <label class="nw-label" :for="stateManual ? 'manual-state' : 'state'">{{ __('State / region') }} <span
                    class="nw-required">*</span></label>
            <select id="state" name="state" class="nw-select" x-model="state" x-on:change="loadCities()"
                x-show="!stateManual" :disabled="stateManual || !countryCode || loadingStates" required
                aria-describedby="state-hint state-error" :aria-invalid="error('state') ? 'true' : 'false'">
                <option value=""
                    x-text="loadingStates ? @js(__('Loading states…')) : @js(__('Select a state'))">
                </option>
                <template x-for="option in states" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === state"></option>
                </template>
            </select>
            <input id="manual-state" name="state" class="nw-input" type="text" x-model="state"
                x-on:change="loadCities()" x-show="stateManual" :disabled="!stateManual || loadingStates" required
                minlength="2" maxlength="255" aria-label="{{ __('State / region') }}"
                aria-describedby="state-hint state-error" />
            <p class="nw-form-hint" id="state-hint" x-show="stateManual">
                {{ __('Regions are not listed for this country. Enter your state or region.') }}
            </p>
            <p class="nw-field__error" id="state-error" role="alert" x-show="error('state')" x-text="error('state')"
                x-cloak></p>
        </div>
        <div class="nw-field nw-form-section__item" data-field="city" data-type="dropdown"
            data-label="{{ __('City') }}" :data-required="countryCode !== 'NG' ? '' : null"
            data-location-field x-show="countryCode !== 'NG'">
            <label class="nw-label" :for="cityManual ? 'manual-city' : 'city'">{{ __('City') }} <span
                    class="nw-required">*</span></label>
            <select id="city" name="city" class="nw-select" x-model="city" x-show="!cityManual"
                :required="countryCode !== 'NG'"
                :disabled="countryCode === 'NG' || cityManual || !state || loadingStates ||
                    loadingCities"
                aria-describedby="city-hint city-error" :aria-invalid="error('city') ? 'true' : 'false'">
                <option value=""
                    x-text="loadingCities ? @js(__('Loading cities…')) : @js(__('Select a city'))">
                </option>
                <template x-for="option in cities" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === city"></option>
                </template>
            </select>
            <input id="manual-city" name="city" class="nw-input" type="text" x-model="city" x-show="cityManual"
                :required="countryCode !== 'NG'"
                :disabled="countryCode === 'NG' || !cityManual || !state || loadingStates ||
                    loadingCities"
                minlength="2" maxlength="255" aria-label="{{ __('City') }}"
                aria-describedby="city-hint city-error" />
            <p class="nw-form-hint" id="city-hint" x-show="cityManual">
                {{ __('Cities are not listed for this state. Enter your city.') }}</p>
            <p class="nw-field__error" id="city-error" role="alert" x-show="error('city')" x-text="error('city')"
                x-cloak></p>
        </div>
        <div class="nw-field nw-form-section__item" data-field="local_government_area" data-type="dropdown"
            data-label="{{ __('Local government area') }}" :data-required="countryCode === 'NG' ? '' : null"
            data-location-field x-show="countryCode === 'NG'">
            <label class="nw-label" for="local_government_area">{{ __('Local government area') }}
                <span class="nw-required">*</span></label>
            <select id="local_government_area" name="local_government_area" class="nw-select" x-model="lga"
                :required="countryCode === 'NG'"
                :disabled="countryCode !== 'NG' || !state || loadingStates || loadingCities"
                aria-describedby="lga-error" :aria-invalid="error('local_government_area') ? 'true' : 'false'">
                <option value=""
                    x-text="loadingCities ? @js(__('Loading areas…')) : @js(__('Select a local government area'))">
                </option>
                <template x-for="option in cities" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === lga"></option>
                </template>
            </select>
            <p class="nw-field__error" id="lga-error" role="alert" x-show="error('local_government_area')"
                x-text="error('local_government_area')" x-cloak></p>
        </div>
        <div class="nw-form-section__item nw-location-feedback" role="status" aria-live="polite">
            <p x-show="loadingStates || loadingCities" x-cloak>
                {{ __('Loading location options…') }}</p>
            <p x-show="statesError" x-cloak><span x-text="statesError"></span> <button type="button"
                    class="nw-link" x-on:click="loadStates(true)"
                    :disabled="loadingStates">{{ __('Retry states') }}</button></p>
            <p x-show="citiesError" x-cloak><span x-text="citiesError"></span> <button type="button"
                    class="nw-link" x-on:click="loadCities(true)"
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
        ]" :value="old('address', $user->address)" /></div>
    </div>
</fieldset>
