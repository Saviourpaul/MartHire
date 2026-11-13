<x-app-layout>
    <x-slot name="title">{{ __('Settings') }}</x-slot>

    <x-slot name="header">
        <h1>{{ __('Settings') }}</h1>
        <p>{{ __('Manage your preferences and account experience.') }}</p>
    </x-slot>

    <div class="nw-settings-page">
        <section id="appearance" class="nw-panel nw-settings-card">
            <header class="nw-settings-card__head">
                <h2>{{ __('Appearance & regional settings') }}</h2>
                <p>{{ __('Choose the timezone used to display dates and times.') }}</p>
            </header>
            <form method="POST" action="{{ route('settings.appearance.update') }}" class="nw-settings-form">
                @csrf
                @method('PATCH')
                <div class="nw-field">
                    <label class="nw-label" for="timezone">{{ __('Timezone') }}</label>
                    <select class="nw-input" id="timezone" name="timezone">
                        <option value="">{{ __('Use the default timezone') }}</option>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $userPreferences->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="nw-field__error">{{ $message }}</p> @enderror
                </div>
                <br>
                <div class="nw-settings-form__actions">
                    <button class="nw-button nw-button--primary" type="submit">{{ __('Save settings') }}</button>
                </div>
            </form>
        </section>

        <section id="notification-sound" class="nw-panel nw-settings-card">
            <header class="nw-settings-card__head">
                <h2>{{ __('In-app notification sound') }}</h2>
                <p>{{ __('Choose whether Marthire plays a short sound when new notifications arrive. This setting is saved to your account.') }}</p>
            </header>
            <div class="nw-notification-sound-setting">
                <div class="nw-notification-sound-setting__copy">
                    <h3 id="notification-sound-label">{{ __('Play a sound for new notifications') }}</h3>
                    <p id="notification-sound-description">{{ __('When enabled, a short preview plays after you switch it on.') }}</p>
                </div>
                <button type="button" class="nw-toggle-switch" role="switch"
                    aria-checked="{{ $userPreferences->notification_sound_enabled ? 'true' : 'false' }}"
                    aria-labelledby="notification-sound-label" aria-describedby="notification-sound-description"
                    data-notification-sound-toggle>
                    <span class="nw-toggle-switch__track" aria-hidden="true"><span></span></span>
                </button>
            </div>
            <p class="nw-sr-only" role="status" aria-live="polite" data-notification-sound-status></p>
        </section>

        @if (! empty($categories))
            <section id="notification-preferences" class="nw-panel nw-settings-card">
                <header class="nw-settings-card__head">
                    <h2>{{ __('Email notifications') }}</h2>
                    <p>{{ __('Choose which workflow updates may also be sent by email. In-app notifications remain available in your notification inbox.') }}</p>
                </header>
                <form method="POST" action="{{ route('settings.notifications.update') }}" class="nw-settings-form">
                    @csrf
                    @method('PATCH')
                    @foreach ($categories as $category)
                        <fieldset class="nw-fieldset">
                            <legend class="nw-label">{{ $category['label'] }}</legend>
                            <p class="nw-hint">{{ $category['description'] }}</p>
                            <label class="nw-checkbox-label">
                                <input type="hidden" name="email_preferences[{{ $category['value'] }}]" value="0">
                                <input class="nw-checkbox" type="checkbox" name="email_preferences[{{ $category['value'] }}]" value="1" @checked(old('email_preferences.'.$category['value'], $emailPreferences[$category['value']] ?? false))>
                                <span>{{ __('Send this update to my account email') }}</span>
                            </label>
                            @if ($notificationOrganization)
                                <label class="nw-field" for="membership-email-{{ $category['value'] }}">
                                    <span class="nw-label">{{ __('For :organization', ['organization' => $notificationOrganization->name]) }}</span>
                                    <select class="nw-input" id="membership-email-{{ $category['value'] }}" name="membership_email_preferences[{{ $category['value'] }}]">
                                        <option value="" @selected(old('membership_email_preferences.'.$category['value'], $membershipEmailPreferences[$category['value']] ?? '') === '')>{{ __('Use my account preference') }}</option>
                                        <option value="on" @selected(old('membership_email_preferences.'.$category['value'], $membershipEmailPreferences[$category['value']] ?? '') === true || old('membership_email_preferences.'.$category['value'], $membershipEmailPreferences[$category['value']] ?? '') === 'on')>{{ __('Always email me for this organization') }}</option>
                                        <option value="off" @selected(old('membership_email_preferences.'.$category['value'], $membershipEmailPreferences[$category['value']] ?? '') === false || old('membership_email_preferences.'.$category['value'], $membershipEmailPreferences[$category['value']] ?? '') === 'off')>{{ __('Never email me for this organization') }}</option>
                                    </select>
                                </label>
                            @endif
                        </fieldset>
                    @endforeach
                    <div class="nw-settings-form__actions">
                        <button class="nw-button nw-button--primary" type="submit">{{ __('Save notification preferences') }}</button>
                    </div>
                </form>
            </section>
        @endif

        @if ($organizationSettings)
            @php($companyLogoUrl = $organizationSettings->companyLogoUrl())
            <section id="organization-profile" class="nw-panel nw-settings-card">
                <header class="nw-settings-card__head">
                    <h2>{{ __('Organization profile and hiring defaults') }}</h2>
                    <p>{{ __('Manage your public company details, logo, and defaults used when arranging interviews and applications.') }}</p>
                    @if ($organization?->slug)
                        <a class="nw-link" href="{{ route('organizations.careers.show', $organization->slug) }}" target="_blank" rel="noopener">{{ __('Preview public careers page') }} <x-icon name="arrow-up-right" /></a>
                    @endif
                </header>
                <form method="POST" action="{{ route('settings.organization.update') }}" enctype="multipart/form-data" class="nw-settings-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="version" value="{{ old('version', $organizationSettings->version) }}">

                    <div class="nw-form-grid">
                        <div class="nw-field">
                            <label class="nw-label" for="organization_name">{{ __('Company name') }}</label>
                            <input class="nw-input" id="organization_name" name="name" type="text" value="{{ old('name', $organization->name) }}" maxlength="255" required autocomplete="organization">
                            @error('name') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="careers_url">{{ __('Careers page') }}</label>
                            <input class="nw-input" id="careers_url" name="careers_url" type="url" value="{{ old('careers_url', $organizationSettings->careers_url) }}" placeholder="https://" autocomplete="url">
                            @error('careers_url') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="public_contact_email">{{ __('Public contact email') }}</label>
                            <input class="nw-input" id="public_contact_email" name="public_contact_email" type="email" value="{{ old('public_contact_email', $organizationSettings->public_contact_email) }}" autocomplete="email">
                            @error('public_contact_email') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="public_contact_phone">{{ __('Public contact phone') }}</label>
                            <input class="nw-input" id="public_contact_phone" name="public_contact_phone" type="tel" value="{{ old('public_contact_phone', $organizationSettings->public_contact_phone) }}" autocomplete="tel">
                            @error('public_contact_phone') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field nw-field--full">
                            <label class="nw-label" for="organization_about">{{ __('About the organization') }}</label>
                            <textarea class="nw-input" id="organization_about" name="about" rows="4" maxlength="2000">{{ old('about', $organizationSettings->about) }}</textarea>
                            @error('about') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="organization_industry">{{ __('Industry') }}</label>
                            <input class="nw-input" id="organization_industry" name="industry" type="text" value="{{ old('industry', $organizationSettings->industry) }}" maxlength="100">
                            @error('industry') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="organization_size">{{ __('Organization size') }}</label>
                            <select class="nw-input" id="organization_size" name="organization_size">
                                <option value="">{{ __('Choose a range') }}</option>
                                @foreach (['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'] as $size)
                                    <option value="{{ $size }}" @selected(old('organization_size', $organizationSettings->organization_size) === $size)>{{ $size }} {{ __('employees') }}</option>
                                @endforeach
                            </select>
                            @error('organization_size') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="organization_primary_color">{{ __('Primary brand color') }}</label>
                            <input class="nw-input nw-color-input" id="organization_primary_color" name="brand_primary_color" type="color" value="{{ old('brand_primary_color', $organizationSettings->brand_primary_color ?: '#006b43') }}">
                            @error('brand_primary_color') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="organization_accent_color">{{ __('Accent color') }}</label>
                            <input class="nw-input nw-color-input" id="organization_accent_color" name="brand_accent_color" type="color" value="{{ old('brand_accent_color', $organizationSettings->brand_accent_color ?: '#c9f542') }}">
                            @error('brand_accent_color') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="nw-field nw-organization-image-upload" x-data="organizationImageUpload({ savedUrl: @js($companyLogoUrl), removeSavedImage: @js((bool) old('remove_company_logo')), maxBytes: 2097152, allowedTypes: ['image/jpeg', 'image/png', 'image/webp'], allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'], messages: { invalidType: @js(__('Choose a JPG, PNG, or WebP image.')), tooLarge: @js(__('Choose an image smaller than 2 MB.')), previewFailed: @js(__('This image could not be previewed. Choose another file.')) } })">
                        <label class="nw-label" for="company_logo">{{ __('Company logo') }}</label>
                        <input class="nw-input" id="company_logo" name="company_logo" type="file" accept="image/png,image/jpeg,image/webp" x-ref="imageInput" x-on:change="previewImage($event)" aria-describedby="company-logo-hint">
                        <p class="nw-hint" id="company-logo-hint">{{ __('PNG, JPG, or WebP, up to 2 MB. Selecting another file replaces the preview. The logo appears on your public job listings.') }}</p>
                        <p class="nw-field__error" x-show="imageError" x-cloak x-text="imageError" role="alert"></p>
                        <div class="nw-settings-company-logo" x-show="previewUrl" x-cloak>
                            <img class="nw-settings-company-logo__image" :src="previewUrl" alt="{{ __('Organization logo preview') }}" x-on:error="imagePreviewFailed($refs.imageInput)">
                            <span class="nw-organization-image-upload__status" x-text="hasSelectedImage ? '{{ __('Selected image preview') }}' : '{{ __('Current logo') }}'"></span>
                        </div>
                        <p class="nw-hint" x-show="!previewUrl" x-cloak>{{ __('No organization logo selected.') }}</p>
                        <button class="nw-button nw-button--outline nw-button--small nw-organization-image-upload__remove" type="button" x-show="hasSelectedImage" x-cloak x-on:click="clearSelectedImage($refs.imageInput)">{{ __('Remove selected image') }}</button>
                        @if ($companyLogoUrl)
                            <label class="nw-checkbox-label nw-settings-company-logo__remove">
                                <input class="nw-checkbox" type="checkbox" name="remove_company_logo" value="1" x-model="removeSavedImage" x-on:change="toggleSavedImageRemoval($event, $refs.imageInput)" x-bind:disabled="hasSelectedImage">
                                <span>{{ __('Remove current logo') }}</span>
                            </label>
                        @endif
                        @error('company_logo') <p class="nw-field__error">{{ $message }}</p> @enderror
                        @error('remove_company_logo') <p class="nw-field__error">{{ $message }}</p> @enderror
                    </div>

                    <div class="nw-form-grid">
                        <div class="nw-field">
                            <label class="nw-label" for="default_interview_timezone">{{ __('Default interview timezone') }}</label>
                            <select class="nw-input" id="default_interview_timezone" name="default_interview_timezone">
                                <option value="">{{ __('Use the candidate’s timezone') }}</option>
                                @foreach ($timezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('default_interview_timezone', $organizationSettings->default_interview_timezone) === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                            @error('default_interview_timezone') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="default_employment_type">{{ __('Default employment type') }}</label>
                            <select class="nw-input" id="default_employment_type" name="default_employment_type">
                                <option value="">{{ __('No default') }}</option>
                                @foreach ($jobTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_employment_type', $organizationSettings->default_employment_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('default_employment_type') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="default_interview_contact_name">{{ __('Default interview contact') }}</label>
                            <input class="nw-input" id="default_interview_contact_name" name="default_interview_contact_name" type="text" value="{{ old('default_interview_contact_name', $organizationSettings->default_interview_contact_name) }}" maxlength="120" autocomplete="name">
                            @error('default_interview_contact_name') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="default_interview_contact_phone">{{ __('Interview contact phone') }}</label>
                            <input class="nw-input" id="default_interview_contact_phone" name="default_interview_contact_phone" type="tel" value="{{ old('default_interview_contact_phone', $organizationSettings->default_interview_contact_phone) }}" autocomplete="tel">
                            @error('default_interview_contact_phone') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field nw-field--full">
                            <label class="nw-label" for="default_application_instructions">{{ __('Default application instructions') }}</label>
                            <textarea class="nw-input" id="default_application_instructions" name="default_application_instructions" rows="4" maxlength="2000">{{ old('default_application_instructions', $organizationSettings->default_application_instructions) }}</textarea>
                            @error('default_application_instructions') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="nw-settings-form__actions">
                        <button class="nw-button nw-button--primary" type="submit">{{ __('Save organization settings') }}</button>
                    </div>
                </form>
            </section>
        @endif

        @if ($platformSettings)
            <section id="social-links" class="nw-panel nw-settings-card">
                <header class="nw-settings-card__head">
                    <h2>{{ __('Public social links') }}</h2>
                    <p>{{ __('Manage the social links shown in the public site footer. Leave a field blank to hide that platform.') }}</p>
                </header>
                <form method="POST" action="{{ route('settings.social-links.update') }}" class="nw-settings-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="version" value="{{ $platformSettings->version }}">
                    <div class="nw-form-grid">
                        @foreach ($socialPlatforms as $platform)
                            <div class="nw-field">
                                <label class="nw-label" for="{{ $platform->value }}">{{ $platform->label() }}</label>
                                <input class="nw-input" id="{{ $platform->value }}" name="{{ $platform->value }}" type="url" value="{{ old($platform->value, $platformSettings->getAttribute($platform->value)) }}" placeholder="https://" autocomplete="url">
                                @error($platform->value) <p class="nw-field__error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="nw-settings-form__actions">
                        <button class="nw-button nw-button--primary" type="submit">{{ __('Save social links') }}</button>
                    </div>
                </form>
            </section>
        @endif

        @if ($jobPreferences)
            @include('settings.partials.candidate-job-preferences-form', ['returnTo' => 'settings'])
        @endif
    </div>
</x-app-layout>
