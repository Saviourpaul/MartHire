<div class="nw-profile-growth-notice" x-data="{ visible: true }" x-show="visible" x-cloak>
    <span class="nw-profile-growth-notice__icon" aria-hidden="true">i</span>
    <div>
        <strong>{{ __('Your profile will keep growing') }}</strong>
        <p>{{ __('This is where your profile lives today. We are expanding your profile with new ways to introduce yourself beyond your résumé, including voice introductions, references, and more.') }}</p>
    </div>
    <button type="button" class="nw-profile-growth-notice__close" aria-label="{{ __('Dismiss notice') }}" x-on:click="visible = false">×</button>
</div>

<section id="resume" class="nw-panel nw-profile-card">
    <header class="nw-panel-heading">
        <span class="nw-eyebrow">{{ __('Your résumé') }}</span>
        <h2>{{ __('Resume') }}</h2>
        <p>{{ __('Upload a résumé to keep it with your profile.') }}</p>
    </header>
    @if ($applicantProfile->resume_path)
        <div class="nw-profile-file">
            <span class="nw-profile-file__icon" aria-hidden="true">PDF</span>
            <div><strong>{{ $applicantProfile->resume_original_name }}</strong><small>{{ $applicantProfile->resume_size ? number_format($applicantProfile->resume_size / 1024).' KB' : '' }}</small></div>
            <a class="nw-link" href="{{ route('profile.resume.download') }}">{{ __('Download') }}</a>
        </div>
    @endif
    <form method="POST" action="{{ route('profile.resume.store') }}" enctype="multipart/form-data" class="nw-profile-upload" x-data="resumeAutofill({ success: @js(__('We filled fields we could recognize. Review and save each section.')), noMatches: @js(__('The résumé was read, but no profile fields could be matched. You can still complete your profile manually.')) })"
          x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false"
          x-on:drop.prevent="dragging = false; $refs.resume.files = $event.dataTransfer.files; $nextTick(() => extract($refs.resume))">
        @csrf
        <label class="nw-profile-upload__drop" x-bind:class="{ 'is-dragging': dragging }" for="resume_file">
            <span class="nw-profile-upload__symbol" aria-hidden="true">↑</span>
            <span>{{ __('Drop your résumé or') }} <strong>{{ __('browse') }}</strong> {{ __('to autofill your profile') }}</span>
            <small>{{ __('DOC, DOCX, TXT, RTF, or PDF · up to 10 MB') }}</small>
            <span class="nw-profile-upload__filename" x-show="fileName" x-text="fileName" x-cloak></span>
        </label>
        <input x-ref="resume" id="resume_file" class="nw-sr-only" name="resume" type="file" accept=".doc,.docx,.txt,.rtf,.pdf" required x-on:change="extract($event.target)">
        <p class="nw-hint" role="status" aria-live="polite" x-show="extracting || message" x-text="extracting ? @js(__('Reading résumé in your browser…')) : message" x-cloak></p>
        @error('resume') <p class="nw-field__error" role="alert">{{ $message }}</p> @enderror
        <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ $applicantProfile->resume_path ? __('Replace résumé') : __('Upload résumé') }}</button></div>
    </form>
    @if ($applicantProfile->resume_path)
        <form method="POST" action="{{ route('profile.resume.destroy') }}" class="nw-profile-remove-form">
            @csrf @method('DELETE')
            <button type="submit" class="nw-link nw-link--danger">{{ __('Remove résumé') }}</button>
        </form>
    @endif
</section>

<section id="contact-information" class="nw-panel nw-profile-card">
    @include('profile.partials.update-profile-information-form')
</section>

<section id="employment" class="nw-panel nw-profile-card">
    <header class="nw-panel-heading"><span class="nw-eyebrow">{{ __('Your experience') }}</span><h2>{{ __('Employment') }}</h2><p>{{ __('Add your recent roles and responsibilities.') }}</p></header>
    @foreach ($applicantProfile->employments as $employment)
        <details class="nw-profile-record">
            <summary><strong>{{ $employment->title }}</strong><span>{{ $employment->company }}@if ($employment->is_current) · {{ __('Current') }}@endif</span></summary>
            <form method="POST" action="{{ route('profile.employments.update', $employment) }}" class="nw-settings-form">
                @csrf @method('PATCH')
                @include('profile.partials.employment-fields', ['employment' => $employment])
                <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Save employment') }}</button></div>
            </form>
            <form method="POST" action="{{ route('profile.employments.destroy', $employment) }}" class="nw-profile-remove-form">
                @csrf @method('DELETE')
                <button class="nw-link nw-link--danger" type="submit">{{ __('Remove role') }}</button>
            </form>
        </details>
    @endforeach
    <details class="nw-profile-add">
        <summary class="nw-button nw-button--outline">{{ __('Add details') }}</summary>
        <form method="POST" action="{{ route('profile.employments.store') }}" class="nw-settings-form">
            @csrf
            @include('profile.partials.employment-fields', ['employment' => null])
            <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Add employment') }}</button></div>
        </form>
    </details>
</section>

<section id="education" class="nw-panel nw-profile-card">
    <header class="nw-panel-heading"><span class="nw-eyebrow">{{ __('Learning and qualifications') }}</span><h2>{{ __('Education') }}</h2><p>{{ __('Add schools, degrees, and other qualifications.') }}</p></header>
    @foreach ($applicantProfile->educations as $education)
        <details class="nw-profile-record">
            <summary><strong>{{ $education->qualification }}</strong><span>{{ $education->institution }}</span></summary>
            <form method="POST" action="{{ route('profile.education.update', $education) }}" class="nw-settings-form">
                @csrf @method('PATCH')
                @include('profile.partials.education-fields', ['education' => $education])
                <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Save education') }}</button></div>
            </form>
            <form method="POST" action="{{ route('profile.education.destroy', $education) }}" class="nw-profile-remove-form">
                @csrf @method('DELETE')
                <button class="nw-link nw-link--danger" type="submit">{{ __('Remove education') }}</button>
            </form>
        </details>
    @endforeach
    <details class="nw-profile-add">
        <summary class="nw-button nw-button--outline">{{ __('Add details') }}</summary>
        <form method="POST" action="{{ route('profile.education.store') }}" class="nw-settings-form">
            @csrf
            @include('profile.partials.education-fields', ['education' => null])
            <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Add education') }}</button></div>
        </form>
    </details>
</section>

<section id="skills" class="nw-panel nw-profile-card">
    <header class="nw-panel-heading"><span class="nw-eyebrow">{{ __('What you do well') }}</span><h2>{{ __('Skills') }}</h2><p>{{ __('Add the skills you want employers to notice.') }}</p></header>
    <form method="POST" action="{{ route('profile.skills.update') }}" class="nw-settings-form">
        @csrf @method('PATCH')
        <div class="nw-field"><label class="nw-label" for="skills">{{ __('Skills') }}</label>
            <input class="nw-input" id="skills" name="skills" type="text" value="{{ old('skills', $applicantProfile->skills->pluck('name')->implode(', ')) }}" placeholder="{{ __('e.g. Project management, Laravel, Data analysis') }}">
            <p class="nw-hint">{{ __('Separate skills with commas. Add up to 30.') }}</p>
            @error('skills') <p class="nw-field__error">{{ $message }}</p> @enderror
            @error('skills.*') <p class="nw-field__error">{{ $message }}</p> @enderror
        </div>
        <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Save skills') }}</button></div>
    </form>
</section>

<section id="online-profiles" class="nw-panel nw-profile-card">
    <header class="nw-panel-heading"><span class="nw-eyebrow">{{ __('Find you online') }}</span><h2>{{ __('Online profiles') }}</h2><p>{{ __('Add professional pages you want to share.') }}</p></header>
    <form method="POST" action="{{ route('profile.details.update') }}" class="nw-settings-form">
        @csrf @method('PATCH')
        <input type="hidden" name="professional_headline" value="{{ $applicantProfile->professional_headline }}">
        <input type="hidden" name="about" value="{{ $applicantProfile->about }}">
        @foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'website' => __('Website')] as $platform => $label)
            <div class="nw-field"><label class="nw-label" for="profile_link_{{ $platform }}">{{ $label }}</label>
                <input class="nw-input" id="profile_link_{{ $platform }}" name="links[{{ $platform }}]" type="url" value="{{ old('links.'.$platform, $applicantProfile->links->firstWhere('platform', $platform)?->url) }}" placeholder="https://">
                @error('links.'.$platform) <p class="nw-field__error">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit">{{ __('Save online profiles') }}</button></div>
    </form>
</section>

@include('settings.partials.candidate-job-preferences-form', ['returnTo' => 'profile'])
