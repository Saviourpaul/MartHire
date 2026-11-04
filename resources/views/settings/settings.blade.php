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

        @if ($organizationSettings)
            @php($companyLogoUrl = $organizationSettings->companyLogoUrl())
            <section id="organization-profile" class="nw-panel nw-settings-card">
                <header class="nw-settings-card__head">
                    <h2>{{ __('Organization profile and hiring defaults') }}</h2>
                    <p>{{ __('Manage your public company details, logo, and defaults used when arranging interviews and applications.') }}</p>
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
                    </div>

                    <div class="nw-field" x-data="{ preview: @js($companyLogoUrl), currentLogo: @js($companyLogoUrl), removeLogo: @js((bool) old('remove_company_logo')), fileSelected: false }">
                        <label class="nw-label" for="company_logo">{{ __('Company logo') }}</label>
                        <input class="nw-input" id="company_logo" name="company_logo" type="file" accept="image/png,image/jpeg,image/webp" x-on:change="const file = $event.target.files[0]; if (file) { fileSelected = true; removeLogo = false; const reader = new FileReader(); reader.onload = event => preview = event.target.result; reader.readAsDataURL(file); } else { fileSelected = false; preview = currentLogo; }">
                        <p class="nw-hint">{{ __('PNG, JPG, or WebP, up to 2 MB. The logo appears on your public job listings.') }}</p>
                        <div class="nw-settings-company-logo" x-show="preview && !removeLogo" x-cloak>
                            <img class="nw-settings-company-logo__image" :src="preview" alt="{{ __('Organization logo preview') }}">
                        </div>
                        @if ($companyLogoUrl)
                            <label class="nw-checkbox-label nw-settings-company-logo__remove">
                                <input class="nw-checkbox" type="checkbox" name="remove_company_logo" value="1" x-model="removeLogo" x-bind:disabled="fileSelected">
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
