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

        @if ($jobPreferences)
            <section id="job-preferences" class="nw-panel nw-settings-card">
                <header class="nw-settings-card__head">
                    <h2>{{ __('Job preferences') }}</h2>
                    <p>{{ __('Choose the roles, locations, and alerts that fit your search.') }}</p>
                </header>
                <form method="POST" action="{{ route('settings.job-preferences.update') }}" class="nw-settings-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="version" value="{{ $jobPreferences->version ?? 0 }}">
                    <div class="nw-form-grid">
                        <div class="nw-field">
                            <label class="nw-label" for="job_alert_frequency">{{ __('Job alert frequency') }}</label>
                            <select class="nw-input" id="job_alert_frequency" name="job_alert_frequency" required>
                                @foreach ($jobAlertFrequencies as $frequency)
                                    <option value="{{ $frequency->value }}" @selected(old('job_alert_frequency', $jobPreferences->job_alert_frequency?->value ?? $jobPreferences->job_alert_frequency) === $frequency->value)>{{ ucfirst(str_replace('_', ' ', $frequency->value)) }}</option>
                                @endforeach
                            </select>
                            @error('job_alert_frequency') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="preferred_job_type">{{ __('Preferred job type') }}</label>
                            <select class="nw-input" id="preferred_job_type" name="preferred_job_type">
                                <option value="">{{ __('Any type') }}</option>
                                @foreach ($jobTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('preferred_job_type', $jobPreferences->preferred_job_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('preferred_job_type') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="preferred_roles">{{ __('Preferred roles') }}</label>
                            <input class="nw-input" id="preferred_roles" name="preferred_roles" type="text" value="{{ old('preferred_roles', implode(', ', $jobPreferences->preferred_roles ?? [])) }}" placeholder="{{ __('e.g. Product Designer, Analyst') }}">
                            <p class="nw-hint">{{ __('Separate roles with commas.') }}</p>
                            @error('preferred_roles') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="preferred_locations">{{ __('Preferred locations') }}</label>
                            <input class="nw-input" id="preferred_locations" name="preferred_locations" type="text" value="{{ old('preferred_locations', implode(', ', $jobPreferences->preferred_locations ?? [])) }}" placeholder="{{ __('e.g. Lagos, Remote') }}">
                            <p class="nw-hint">{{ __('Separate locations with commas.') }}</p>
                            @error('preferred_locations') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="salary_min">{{ __('Minimum salary') }}</label>
                            <input class="nw-input" id="salary_min" name="salary_min" type="number" min="0" max="100000000" value="{{ old('salary_min', $jobPreferences->salary_min) }}">
                            @error('salary_min') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                        <div class="nw-field">
                            <label class="nw-label" for="salary_max">{{ __('Maximum salary') }}</label>
                            <input class="nw-input" id="salary_max" name="salary_max" type="number" min="0" max="100000000" value="{{ old('salary_max', $jobPreferences->salary_max) }}">
                            @error('salary_max') <p class="nw-field__error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                   <br>
                    @error('profile_visible_to_employers') <p class="nw-field__error">{{ $message }}</p> @enderror
                    <div class="nw-settings-form__actions">
                        <button class="nw-button nw-button--primary" type="submit">{{ __('Save preferences') }}</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
