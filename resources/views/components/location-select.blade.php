@props(['field', 'label', 'selected' => '', 'inputClass', 'labelClass', 'errorClass', 'required' => false])

<div data-field>
    <label for="location-{{ $field }}" class="{{ $labelClass }}">{{ $label }}
        @if ($required)<span class="text-error-500" data-location-required-mark="{{ $field }}">*</span>@endif
    </label>
    <select id="location-{{ $field }}" name="{{ $field }}" data-location-{{ $field }}
        data-selected="{{ $selected }}" class="{{ $inputClass }} @error($field) border-red-500 @enderror"
        aria-describedby="location-{{ $field }}-error location-{{ $field }}-status" @required($required && $field === 'country_code')>
        <option value="">{{ $field === 'country_code' ? 'Select country' : ($field === 'state' ? 'Select country first' : 'Select state first') }}</option>
        @if ($selected)<option value="{{ $selected }}" selected>{{ $selected }}</option>@endif
    </select>
    <p id="location-{{ $field }}-error" class="{{ $errorClass }} {{ $errors->has($field) ? 'block' : 'hidden' }}" data-validation-message role="alert">
        @error($field){{ $message }}@enderror
    </p>
    <p id="location-{{ $field }}-status" class="mt-1 text-xs text-gray-500 dark:text-gray-400" data-location-status="{{ $field }}" role="status" aria-live="polite"></p>
    <button type="button" class="mt-1 text-sm text-brand-500 underline" data-location-retry="{{ $field }}" hidden>Retry loading {{ strtolower($label) }}</button>
</div>
