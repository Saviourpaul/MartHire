@props(['id' => 'phone', 'name' => 'phone', 'value' => null, 'required' => false, 'country' => null, 'wizard' => false, 'describedBy' => null])

@php
    $phoneService = app(\App\Services\PhoneNumberService::class);
    $phoneCountries = $phoneService->countries();
    $countryName = $name.'_country';
    $selectedCountry = old($countryName, $country ?? auth()->user()?->phone_country ?? auth()->user()?->country_code ?? config('phone.default_country', 'NG'));
    $selectedCountry = is_string($selectedCountry) ? strtoupper($selectedCountry) : config('phone.default_country', 'NG');
    if (! in_array($selectedCountry, array_column($phoneCountries, 'code'), true)) $selectedCountry = 'NG';
    $explicitCountry = session()->hasOldInput($countryName) || filled(auth()->user()?->phone_country) || $country !== null;
    $serverError = $errors->first($name) ?: $errors->first($countryName);
    $descriptionIds = trim(($describedBy ?? '').' '.$id.'-phone-error');
@endphp

<div class="nw-phone-field" data-phone-field x-data="internationalPhone({ countries: @js($phoneCountries), country: @js($selectedCountry), value: @js(is_scalar($value) ? (string) $value : ''), required: @js((bool) $required), name: @js($name), explicitCountry: @js($explicitCountry), wizard: @js((bool) $wizard), serverError: @js($serverError) })"
     x-on:click.outside="closePhoneCountries()" x-on:keydown.escape.stop="if (countryMenuOpen) closePhoneCountries(true)"
     x-on:focusout="if (!$el.contains($event.relatedTarget)) closePhoneCountries()" x-on:resize.window="if (countryMenuOpen) positionPhoneMenu()">
    <div class="nw-phone" x-bind:class="{ 'has-error': phoneMessage() }">
        <select name="{{ $countryName }}" class="nw-phone__fallback" aria-label="{{ __('Phone country and dialing code') }}" x-show="!phoneEnhanced" x-bind:disabled="phoneEnhanced">
            @foreach ($phoneCountries as $option)<option value="{{ $option['code'] }}" @selected($selectedCountry === $option['code'])>{{ $option['name'] }} {{ $option['dial_code'] }}</option>@endforeach
        </select>
        <button type="button" class="nw-phone__country" x-ref="phoneCountryButton" x-show="phoneEnhanced" x-cloak
                aria-haspopup="dialog" aria-controls="{{ $id }}-country-picker" x-bind:aria-expanded="countryMenuOpen.toString()"
                x-bind:aria-label="'Phone country: ' + (selectedPhoneCountry?.name || '') + ' ' + (selectedPhoneCountry?.dial_code || '')"
                x-on:click="countryMenuOpen ? closePhoneCountries() : openPhoneCountries()" x-on:keydown.arrow-down.prevent="openPhoneCountries()">
            <template x-if="selectedPhoneCountry?.flag_url"><img x-bind:src="selectedPhoneCountry.flag_url" width="20" height="20" alt=""></template>
            <span class="nw-phone__iso" x-text="phoneCountry"></span><span x-text="selectedPhoneCountry?.dial_code"></span><x-icon name="chevron-down" />
        </button>
        <input id="{{ $id }}" name="{{ $name }}" type="tel" class="nw-phone__number" value="{{ is_scalar($value) ? $value : '' }}" x-ref="phoneInput" data-phone-input data-field-control
               inputmode="tel" autocomplete="tel-national" maxlength="80" @required($required) aria-describedby="{{ $descriptionIds }}"
               x-bind:value="nationalValue" x-bind:data-e164="canonicalPhone" x-bind:aria-invalid="phoneMessage() ? 'true' : 'false'"
               x-on:input="phoneNumberInput($event)" x-on:change="if ($event.target.value !== nationalValue) phoneNumberInput($event)"
               x-on:blur="phoneNumberBlur()" x-on:invalid="phoneTouched = true" placeholder="{{ __('Phone number') }}">
        <input type="hidden" name="{{ $countryName }}" x-bind:value="phoneCountry" x-bind:disabled="!phoneEnhanced" disabled>
    </div>
    <div id="{{ $id }}-country-picker" class="nw-phone__picker" x-bind:class="{ 'is-above': phoneMenuAbove }" role="dialog" aria-label="{{ __('Choose phone country') }}" x-show="countryMenuOpen" x-cloak>
        <div class="nw-phone__search">
            <x-icon name="search" />
            <input type="search" class="nw-input" x-ref="phoneSearch" data-phone-search x-model="countryQuery" autocomplete="off"
                   role="combobox" aria-autocomplete="list" aria-label="{{ __('Search country or dialing code') }}" aria-controls="{{ $id }}-country-options" x-bind:aria-expanded="countryMenuOpen.toString()"
                   x-bind:aria-activedescendant="filteredPhoneCountries[activeCountryIndex] ? @js($id.'-country-') + filteredPhoneCountries[activeCountryIndex].code : null"
                   placeholder="{{ __('Search country or code…') }}" x-on:input="searchPhoneCountries()"
                   x-on:keydown.arrow-down.prevent="movePhoneCountry(1)" x-on:keydown.arrow-up.prevent="movePhoneCountry(-1)"
                   x-on:keydown.enter.prevent="chooseActivePhoneCountry()" x-on:keydown.escape.prevent.stop="closePhoneCountries(true)">
        </div>
        <ul id="{{ $id }}-country-options" class="nw-phone__options" role="listbox" aria-label="{{ __('Phone countries') }}" x-ref="phoneOptions">
            <template x-for="(option, index) in filteredPhoneCountries" x-bind:key="option.code">
                <li role="presentation"><button type="button" tabindex="-1" role="option" x-bind:id="@js($id.'-country-') + option.code" x-bind:aria-selected="option.code === phoneCountry ? 'true' : 'false'"
                    x-bind:data-option-index="index" x-bind:class="{ 'is-active': index === activeCountryIndex }" x-on:click="choosePhoneCountry(option.code)">
                    <template x-if="option.flag_url"><img x-bind:src="option.flag_url" width="20" height="20" alt="" loading="lazy"></template>
                    <span x-text="option.name"></span><small x-text="option.dial_code"></small><x-icon name="check" x-show="option.code === phoneCountry" />
                </button></li>
            </template>
        </ul>
        <p class="nw-phone__empty" x-show="!filteredPhoneCountries.length" role="status">{{ __('No countries found.') }}</p>
    </div>
    <p id="{{ $id }}-phone-error" class="nw-field__error" role="alert" x-show="phoneMessage()" x-text="phoneMessage()" @if (!$serverError) x-cloak @endif>{{ $serverError }}</p>
</div>
