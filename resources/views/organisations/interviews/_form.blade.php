@php
    $interview = $interview ?? null;
    $formKey = $interview ? 'edit-'.$interview->id : 'schedule';
    $restore = old('_interview_form') === $formKey;
    $fieldValue = fn (string $key, $default = '') => $restore ? old($key, $default) : $default;
    $selectedTimezone = $fieldValue('timezone', $interview?->timezone ?? $interviewWorkspace['timezone']);
    $selectedMode = $fieldValue('mode', $interview?->mode->value ?? 'online');
    $localStart = $interview?->start_at?->setTimezone($interview->timezone);
    $localEnd = $interview?->end_at?->setTimezone($interview->timezone);
    $fieldId = 'interview-'.$formKey.'-';
@endphp
<form method="POST" action="{{ $interview ? route('organisations.interviews.update', $interview) : route('organisations.interviews.store', $application) }}" class="nw-form" x-data="{ interviewMode: @js($selectedMode) }">
    @csrf
    @if ($interview) @method('PATCH') <input type="hidden" name="version" value="{{ $interview->version }}"> @endif
    <input type="hidden" name="_interview_form" value="{{ $formKey }}">
    @if ($restore && $errors->any())
        <div class="nw-notice nw-notice--error" role="alert">
            <p>{{ __('Please check the interview details.') }}</p>
            <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif
    <p class="nw-hint">{{ __('The candidate receives an email invitation with a confirmation link. Times use the selected interview timezone.') }}</p>
    <div class="nw-form-grid">
        <div class="nw-field">
            <label class="nw-label" for="{{ $fieldId }}mode">{{ __('Interview type') }}</label>
            <select class="nw-input" id="{{ $fieldId }}mode" name="mode" x-model="interviewMode" required>
                <option value="online" @selected($selectedMode === 'online')>{{ __('Online') }}</option>
                <option value="onsite" @selected($selectedMode === 'onsite')>{{ __('Onsite') }}</option>
            </select>
        </div>
        <div class="nw-field">
            <label class="nw-label" for="{{ $fieldId }}timezone">{{ __('Timezone') }}</label>
            <input class="nw-input" type="text" id="{{ $fieldId }}timezone" name="timezone" list="{{ $fieldId }}timezones" value="{{ $selectedTimezone }}" required autocomplete="off">
            <datalist id="{{ $fieldId }}timezones">@foreach ($interviewWorkspace['timezones'] as $timezone)<option value="{{ $timezone }}"></option>@endforeach</datalist>
        </div>
        <div class="nw-field">
            <label class="nw-label" for="{{ $fieldId }}date">{{ __('Interview date') }}</label>
            <input class="nw-input" type="date" id="{{ $fieldId }}date" name="interview_date" value="{{ $fieldValue('interview_date', $localStart?->format('Y-m-d')) }}" required>
        </div>
        <div class="nw-field">
            <label class="nw-label" for="{{ $fieldId }}start">{{ __('Start time') }}</label>
            <input class="nw-input" type="time" id="{{ $fieldId }}start" name="start_time" value="{{ $fieldValue('start_time', $localStart?->format('H:i')) }}" required>
        </div>
        <div class="nw-field">
            <label class="nw-label" for="{{ $fieldId }}end">{{ __('End time') }}</label>
            <input class="nw-input" type="time" id="{{ $fieldId }}end" name="end_time" value="{{ $fieldValue('end_time', $localEnd?->format('H:i')) }}" required>
        </div>
    </div>
    <fieldset class="nw-fieldset" x-show="interviewMode === 'online'" x-bind:disabled="interviewMode !== 'online'" @disabled($selectedMode !== 'online')>
        <legend class="nw-sr-only">{{ __('Online meeting') }}</legend>
        <div class="nw-form-grid">
            <div class="nw-field">
                <label class="nw-label" for="{{ $fieldId }}platform">{{ __('Meeting platform') }}</label>
                <select class="nw-input" id="{{ $fieldId }}platform" name="platform" required>
                    @foreach (\App\Enums\InterviewPlatform::cases() as $platform)<option value="{{ $platform->value }}" @selected($fieldValue('platform', $interview?->platform?->value ?? 'google_meet') === $platform->value)>{{ $platform->label() }}</option>@endforeach
                </select>
            </div>
            <div class="nw-field">
                <label class="nw-label" for="{{ $fieldId }}link">{{ __('Meeting link') }}</label>
                <input class="nw-input" type="url" id="{{ $fieldId }}link" name="meeting_link" value="{{ $fieldValue('meeting_link', $interview?->meeting_link) }}" maxlength="2000" placeholder="https://" required>
            </div>
        </div>
    </fieldset>
    <fieldset class="nw-fieldset" x-show="interviewMode === 'onsite'" x-bind:disabled="interviewMode !== 'onsite'" @disabled($selectedMode !== 'onsite')>
        <legend class="nw-sr-only">{{ __('Onsite location') }}</legend>
        <div class="nw-form-grid">
            @foreach (['venue_name' => __('Venue'), 'address' => __('Venue address'), 'what_to_bring' => __('What to bring'), 'directions' => __('Directions')] as $key => $label)
                <div class="nw-field">
                    <label class="nw-label" for="{{ $fieldId.$key }}">{{ $label }}</label>
                    <input class="nw-input" type="text" id="{{ $fieldId.$key }}" name="{{ $key }}" value="{{ $fieldValue($key, $interview?->{$key}) }}" maxlength="{{ $key === 'venue_name' ? 255 : 2000 }}" @required(in_array($key, ['venue_name', 'address'], true))>
                </div>
            @endforeach
        </div>
    </fieldset>
    <div class="nw-form-grid">
        <div class="nw-field"><label class="nw-label" for="{{ $fieldId }}contact-name">{{ __('Contact name') }}</label><input class="nw-input" id="{{ $fieldId }}contact-name" name="contact_name" value="{{ $fieldValue('contact_name', $interview?->contact_name ?? $interviewWorkspace['contact_name']) }}" maxlength="120" required></div>
        <div class="nw-field"><label class="nw-label" for="{{ $fieldId }}contact-phone">{{ __('Contact phone') }}</label><input class="nw-input" type="tel" id="{{ $fieldId }}contact-phone" name="contact_phone" value="{{ $fieldValue('contact_phone', $interview?->contact_phone ?? $interviewWorkspace['contact_phone']) }}" maxlength="50"></div>
    </div>
    <div class="nw-field"><label class="nw-label" for="{{ $fieldId }}instructions">{{ __('Candidate instructions') }}</label><textarea class="nw-input" id="{{ $fieldId }}instructions" name="instructions" rows="3" maxlength="5000">{{ $fieldValue('instructions', $interview?->instructions) }}</textarea></div>
    <details class="nw-review-disclosure" @if ($restore && (old('subject') || old('body'))) open @endif>
        <summary>{{ __('Customize invitation email') }} <span class="nw-hint">{{ __('Optional') }}</span></summary>
        <p class="nw-hint">{{ __('Leave blank to use the organization template. Keep the meeting link or venue address placeholder in custom messages.') }}</p>
        <div class="nw-field"><label class="nw-label" for="{{ $fieldId }}subject">{{ __('Email subject') }}</label><input class="nw-input" id="{{ $fieldId }}subject" name="subject" value="{{ $fieldValue('subject') }}" maxlength="255"></div>
        <div class="nw-field"><label class="nw-label" for="{{ $fieldId }}body">{{ __('Email message') }}</label><textarea class="nw-input" id="{{ $fieldId }}body" name="body" rows="5" maxlength="20000">{{ $fieldValue('body') }}</textarea></div>
    </details>
    <div class="nw-button-row"><button class="nw-button nw-button--primary" type="submit">{{ $interview ? __('Save changes and send update') : __('Schedule and send invitation') }}</button></div>
</form>
