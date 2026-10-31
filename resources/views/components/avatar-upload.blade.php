@props([
    'user',
    'id' => 'profile_image',
    'name' => 'profile_image',
    'required' => false,
    'wizard' => false,
    'types' => ['jpg', 'jpeg', 'png', 'webp'],
    'maxBytes' => 5242880,
    'hint' => null,
])

@php
    $initials = collect([$user->first_name, $user->last_name])->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $initials = $initials ?: mb_strtoupper(mb_substr($user->name ?: $user->email, 0, 1));
    $savedUrl = filled($user->profile_image_path) ? $user->profileImageUrl() : null;
    $accept = collect($types)->map(fn ($type) => '.'.ltrim($type, '.'))->implode(',');
@endphp

<div class="nw-field nw-avatar-field" @if ($wizard) data-field="{{ $name }}" data-type="file" data-label="{{ __('Profile photo') }}" data-accept="{{ implode(',', $types) }}" data-max="{{ $maxBytes }}" @if ($required) data-required @endif @endif>
    <div class="nw-avatar-upload nw-avatar-upload--apex" x-data="avatarUpload({ savedUrl: @js($savedUrl) })">
        <span class="nw-avatar-upload__preview" aria-hidden="true">
            <span class="nw-avatar-upload__initials" x-show="!avatarUrl || avatarFailed">{{ $initials }}</span>
            <template x-if="avatarUrl && !avatarFailed">
                <img :src="avatarUrl" alt="" class="nw-avatar-upload__image" width="80" height="80" x-on:error="avatarFailed = true">
            </template>
        </span>
        <div class="nw-avatar-upload__details">
            <label for="{{ $id }}" class="nw-button nw-button--outline nw-button--small">
                {{ __('Change avatar') }} @if ($required)<span class="nw-required" aria-hidden="true">*</span>@endif
            </label>
            <button type="button" class="nw-link nw-link--small" x-show="avatarFilename" x-cloak
                    x-on:click="$refs.avatarInput.value = ''; $refs.avatarInput.dispatchEvent(new Event('change', { bubbles: true }))">
                {{ __('Undo change') }}
            </button>
            @if ($hint)<p class="nw-form-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
            <p class="nw-avatar-upload__filename" x-show="avatarFilename" x-text="avatarFilename" x-cloak></p>
            <input id="{{ $id }}" name="{{ $name }}" x-ref="avatarInput" class="nw-sr-only" type="file" accept="{{ $accept }}" @required($required)
                   aria-label="{{ __('Profile photo') }}" aria-describedby="{{ $id }}-hint {{ $id }}-error"
                   @if ($wizard) x-bind:aria-invalid="error(@js($name)) ? 'true' : 'false'" @else aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @endif
                   x-on:change="@if ($wizard) pick($event); @endif previewAvatar($event)">
        </div>
    </div>
    @if ($wizard)
        <p class="nw-field__error" id="{{ $id }}-error" role="alert" x-show="error(@js($name))" x-text="error(@js($name))" x-cloak></p>
    @else
        <x-input-error :id="$id.'-error'" :messages="$errors->get($name)" />
    @endif
</div>
