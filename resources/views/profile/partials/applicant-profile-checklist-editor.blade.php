<div class="nw-modal-content nw-profile-editor" data-applicant-profile-editor-content>
    <header class="nw-profile-editor__header">
        <div>
            
        </div>
        <button class="nw-profile-editor__close" type="button" aria-label="{{ __('Close') }}"
            x-on:click="$dispatch('close-modal', 'applicant-profile-checklist-editor')">×</button>
    </header>

    <p class="nw-profile-editor__status" role="status" aria-live="polite" x-show="successMessage"
        x-text="successMessage" x-cloak></p>

    <section x-show="activeSection === 'resume'" x-cloak aria-labelledby="profile-editor-resume-heading">
        <h3 id="profile-editor-resume-heading">{{ __('Resume') }}</h3>
        <p class="nw-hint">{{ __('Your résumé is stored privately and can only be downloaded by you.') }}</p>
        @if ($applicantProfile->resume_path)
            <div class="nw-profile-file">
                <span class="nw-profile-file__icon" aria-hidden="true">PDF</span>
                <div>
                    <strong>{{ $applicantProfile->resume_original_name }}</strong><small>{{ $applicantProfile->resume_size ? number_format($applicantProfile->resume_size / 1024) . ' KB' : '' }}</small>
                </div>
                <a class="nw-link" href="{{ route('profile.resume.download') }}">{{ __('Download') }}</a>
            </div>
        @endif
        <form method="POST" action="{{ route('profile.resume.store') }}" enctype="multipart/form-data"
            class="nw-settings-form" data-applicant-profile-async-form>
            @csrf
            <div class="nw-field">
                <label class="nw-label"
                    for="checklist_resume">{{ $applicantProfile->resume_path ? __('Replace résumé') : __('Upload résumé') }}</label>
                <input class="nw-input" id="checklist_resume" name="resume" type="file"
                    accept=".doc,.docx,.txt,.rtf,.pdf" required>
                <p class="nw-hint">{{ __('DOC, DOCX, TXT, RTF, or PDF · up to 10 MB') }}</p>
            </div>
            <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary" type="submit"
                    data-submit-label="{{ __('Upload résumé') }}">{{ __('Save résumé') }}</button></div>
        </form>
        @if ($applicantProfile->resume_path)
            <form method="POST" action="{{ route('profile.resume.destroy') }}" class="nw-profile-remove-form"
                data-applicant-profile-async-form>
                @csrf @method('DELETE')
                <button type="submit" class="nw-link nw-link--danger">{{ __('Remove résumé') }}</button>
            </form>
        @endif
    </section>

    <section x-show="activeSection === 'contact'" x-cloak aria-labelledby="profile-editor-contact-heading">
        @include('profile.partials.update-profile-information-form')
    </section>

    <section x-show="activeSection === 'employment'" x-cloak aria-labelledby="profile-editor-employment-heading">
        <h3 id="profile-editor-employment-heading">{{ __('Employment and skills') }}</h3>
        @foreach ($applicantProfile->employments as $employment)
            <details class="nw-profile-record">
                <summary><strong>{{ $employment->title }}</strong><span>{{ $employment->company }}</span></summary>
                <form method="POST" action="{{ route('profile.employments.update', $employment) }}"
                    class="nw-settings-form" data-applicant-profile-async-form>
                    @csrf @method('PATCH')
                    @include('profile.partials.employment-fields', ['employment' => $employment])
                    <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                            type="submit">{{ __('Save employment') }}</button></div>
                </form>
            </details>
        @endforeach
        <details class="nw-profile-add" @if ($applicantProfile->employments->isEmpty()) open @endif>
            <summary class="nw-button nw-button--outline">{{ __('Add employment') }}</summary>
            <form method="POST" action="{{ route('profile.employments.store') }}" class="nw-settings-form"
                data-applicant-profile-async-form>
                @csrf
                @include('profile.partials.employment-fields', ['employment' => null])
                <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                        type="submit">{{ __('Add employment') }}</button></div>
            </form>
        </details>
        <form method="POST" action="{{ route('profile.skills.update') }}"
            class="nw-settings-form nw-profile-editor__skills" data-applicant-profile-async-form>
            @csrf @method('PATCH')
            <div class="nw-field">
                <label class="nw-label" for="checklist_skills">{{ __('Skills') }}</label>
                <input class="nw-input" id="checklist_skills" name="skills" type="text"
                    value="{{ $applicantProfile->skills->pluck('name')->implode(', ') }}"
                    placeholder="{{ __('e.g. Project management, Laravel, Data analysis') }}">
                <p class="nw-hint">{{ __('Separate skills with commas. Add up to 30.') }}</p>
            </div>
            <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                    type="submit">{{ __('Save skills') }}</button></div>
        </form>
    </section>

    <section x-show="activeSection === 'education'" x-cloak aria-labelledby="profile-editor-education-heading">
        <h3 id="profile-editor-education-heading">{{ __('Education') }}</h3>
        @foreach ($applicantProfile->educations as $education)
            <details class="nw-profile-record">
                <summary><strong>{{ $education->qualification }}</strong><span>{{ $education->institution }}</span>
                </summary>
                <form method="POST" action="{{ route('profile.education.update', $education) }}"
                    class="nw-settings-form" data-applicant-profile-async-form>
                    @csrf @method('PATCH')
                    @include('profile.partials.education-fields', ['education' => $education])
                    <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                            type="submit">{{ __('Save education') }}</button></div>
                </form>
            </details>
        @endforeach
        <details class="nw-profile-add" @if ($applicantProfile->educations->isEmpty()) open @endif>
            <summary class="nw-button nw-button--outline">{{ __('Add education') }}</summary>
            <form method="POST" action="{{ route('profile.education.store') }}" class="nw-settings-form"
                data-applicant-profile-async-form>
                @csrf
                @include('profile.partials.education-fields', ['education' => null])
                <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                        type="submit">{{ __('Add education') }}</button></div>
            </form>
        </details>
    </section>

    <section x-show="activeSection === 'online_profiles'" x-cloak aria-labelledby="profile-editor-links-heading">
        <h3 id="profile-editor-links-heading">{{ __('Online profiles') }}</h3>
        <form method="POST" action="{{ route('profile.details.update') }}" class="nw-settings-form"
            data-applicant-profile-async-form>
            @csrf @method('PATCH')
            <input type="hidden" name="professional_headline"
                value="{{ $applicantProfile->professional_headline }}">
            <input type="hidden" name="about" value="{{ $applicantProfile->about }}">
            @foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'website' => __('Website')] as $platform => $label)
                <div class="nw-field">
                    <label class="nw-label" for="checklist_link_{{ $platform }}">{{ $label }}</label>
                    <input class="nw-input" id="checklist_link_{{ $platform }}"
                        name="links[{{ $platform }}]" type="url"
                        value="{{ $applicantProfile->links->firstWhere('platform', $platform)?->url }}"
                        placeholder="https://">
                </div>
            @endforeach
            <div class="nw-settings-form__actions"><button class="nw-button nw-button--primary"
                    type="submit">{{ __('Save online profiles') }}</button></div>
        </form>
    </section>

    <section x-show="activeSection === 'work_preferences'" x-cloak
        aria-labelledby="profile-editor-work-preferences-heading">
        <h3 id="profile-editor-work-preferences-heading">{{ __('Work preferences') }}</h3>
        @include('settings.partials.candidate-job-preferences-form', ['returnTo' => 'settings'])
    </section>

    
</div>
