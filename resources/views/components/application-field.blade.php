@props(['field', 'value' => null])

{{--
    One field of the application form. $field is a normalised definition:
    name (used for errors and ids), input_name (the name="" attribute, defaults to name), label, type,
    required, hint, choices, single_answer, accept (file extensions), max_file_size, max_length.
    Errors and files are tracked by the applicationForm() script in resources/js/application-form.js.
--}}

@php
    $name = $field['name'];
    $inputName = $field['input_name'] ?? $name;
    $type = $field['type'];
    $id = 'field-'.\Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], '-', $name));
    $required = ! empty($field['required']);
    $hint = $field['hint'] ?? null;
    $describedBy = trim(($hint ? "{$id}-hint " : '')."{$id}-error");
    $maxLength = $field['max_length'] ?? null;
    $accept = collect($field['accept'] ?? [])->map(fn ($extension) => '.'.ltrim($extension, '.'))->implode(',');
    $choices = $field['choices'] ?? [];
    $single = $field['single_answer'] ?? true;
    $isGroup = in_array($type, ['boolean', 'multiple_choice'], true);
    $textual = ['text', 'short_text', 'email', 'tel', 'numeric', 'date'];
    $htmlType = ['email' => 'email', 'tel' => 'tel', 'numeric' => 'number', 'date' => 'date'][$type] ?? 'text';
@endphp

<div class="nw-field" data-field="{{ $name }}" data-type="{{ $type }}" data-label="{{ $field['label'] }}" @if ($required) data-required @endif @if ($type === 'file') data-accept="{{ implode(',', $field['accept'] ?? []) }}" data-max="{{ $field['max_file_size'] ?? '' }}" @endif @if ($maxLength && in_array($type, ['textarea', 'free_text'], true)) x-data="{ length: {{ mb_strlen((string) $value) }} }" @endif>
    @if ($isGroup)
        <fieldset class="nw-fieldset" aria-describedby="{{ $describedBy }}">
            <legend class="nw-label">
                {{ $field['label'] }}
                @if ($required)<span class="nw-required" aria-hidden="true">*</span><span class="nw-sr-only"> ({{ __('required') }})</span>@else<span class="nw-optional">{{ __('Optional') }}</span>@endif
            </legend>
            @if ($hint)<p class="nw-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif

            <div class="nw-choices {{ $type === 'boolean' ? 'nw-choices--inline' : '' }}">
                @if ($type === 'boolean')
                    @foreach ([['1', __('Yes')], ['0', __('No')]] as [$optionValue, $optionLabel])
                        <label class="nw-choice">
                            <input type="radio" name="{{ $inputName }}" value="{{ $optionValue }}" @checked((string) $value === $optionValue) x-on:change="clear(@js($name))">
                            <span>{{ $optionLabel }}</span>
                        </label>
                    @endforeach
                @else
                    @foreach ($choices as $choice)
                        <label class="nw-choice">
                            <input type="{{ $single ? 'radio' : 'checkbox' }}" name="{{ $inputName }}" value="{{ $choice['id'] }}" @checked(in_array($choice['id'], (array) $value, true)) x-on:change="clear(@js($name))">
                            <span>{{ $choice['body'] }}</span>
                        </label>
                    @endforeach
                @endif
            </div>
        </fieldset>
    @else
        <label class="nw-label" for="{{ $id }}">
            {{ $field['label'] }}
            @if ($required)<span class="nw-required" aria-hidden="true">*</span><span class="nw-sr-only"> ({{ __('required') }})</span>@else<span class="nw-optional">{{ __('Optional') }}</span>@endif
        </label>
        @if ($hint)<p class="nw-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif

        @if (in_array($type, $textual, true))
            <input class="nw-input" id="{{ $id }}" type="{{ $htmlType }}" name="{{ $inputName }}" value="{{ $value }}" @if (! empty($field['autocomplete'])) autocomplete="{{ $field['autocomplete'] }}" @endif @if ($maxLength) maxlength="{{ $maxLength }}" @endif @if ($type === 'numeric') step="any" inputmode="decimal" min="0" @endif aria-describedby="{{ $describedBy }}" x-bind:aria-invalid="error(@js($name)) ? 'true' : 'false'" x-on:input="clear(@js($name))">
        @elseif (in_array($type, ['textarea', 'free_text'], true))
            <textarea class="nw-input nw-textarea" id="{{ $id }}" name="{{ $inputName }}" rows="{{ $type === 'free_text' ? 4 : 6 }}" @if ($maxLength) maxlength="{{ $maxLength }}" @endif aria-describedby="{{ $describedBy }}" x-bind:aria-invalid="error(@js($name)) ? 'true' : 'false'" x-on:input="clear(@js($name)); @if ($maxLength) length = $event.target.value.length @endif">{{ $value }}</textarea>
            @if ($maxLength)<p class="nw-counter" aria-hidden="true"><span x-text="length">{{ mb_strlen((string) $value) }}</span> / {{ number_format($maxLength) }}</p>@endif
        @elseif ($type === 'dropdown')
            <select class="nw-input" id="{{ $id }}" name="{{ $inputName }}" aria-describedby="{{ $describedBy }}" x-bind:aria-invalid="error(@js($name)) ? 'true' : 'false'" x-on:change="clear(@js($name))">
                <option value="">{{ __('Choose an option') }}</option>
                @foreach ($choices as $choice)
                    <option value="{{ $choice['id'] }}" @selected((string) $value === (string) $choice['id'])>{{ $choice['body'] }}</option>
                @endforeach
            </select>
        @elseif ($type === 'file')
            <label class="nw-dropzone" for="{{ $id }}" x-bind:class="{ 'is-over': over === @js($name), 'has-file': files[@js($name)] }" x-on:dragover.prevent="over = @js($name)" x-on:dragleave.prevent="over = null" x-on:drop.prevent="drop($event, @js($name))">
                <input class="nw-sr-only" id="{{ $id }}" type="file" name="{{ $inputName }}" @if ($accept) accept="{{ $accept }}" @endif aria-describedby="{{ $describedBy }}" x-bind:aria-invalid="error(@js($name)) ? 'true' : 'false'" x-on:change="pick($event)">
                <x-icon name="file" />
                <span class="nw-dropzone__text"><strong>{{ __('Choose a file') }}</strong> {{ __('or drag it here') }}</span>
            </label>
            <div class="nw-file" x-show="files[@js($name)]" x-cloak>
                <x-icon name="check" />
                <span class="nw-file__name" x-text="files[@js($name)] ? files[@js($name)].name : ''"></span>
                <small x-text="files[@js($name)] ? files[@js($name)].size : ''"></small>
                <button type="button" class="nw-link nw-link--small" x-on:click="remove(@js($name))">{{ __('Remove') }}<span class="nw-sr-only"> {{ $field['label'] }}</span></button>
            </div>
        @endif
    @endif

    <p class="nw-field__error" id="{{ $id }}-error" role="alert" x-show="error(@js($name))" x-text="error(@js($name))" x-cloak></p>
</div>
