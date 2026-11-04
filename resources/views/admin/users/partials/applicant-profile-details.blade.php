@php
    $applicantProfile = $managedUser->applicantProfile;
    $identity = $applicantProfile?->selfIdentification;
    $preferences = $managedUser->jobPreferences;
    $adminApplicantDocuments = $managedUser->getRelation('adminApplicantDocuments') ?? collect();
    $genderLabel = match ($identity?->gender_identity) {
        'male' => __('Male'),
        'female' => __('Female'),
        default => '—',
    };
    $disabilityLabel = match ($identity?->disability_status) {
        'yes' => __('Yes'),
        'no' => __('No'),
        default => '—',
    };
@endphp

<section class="nw-admin-applicant-profile" aria-labelledby="applicant-profile-{{ $managedUser->id }}-heading">
    <header class="nw-admin-applicant-profile__header">
        <h3 id="applicant-profile-{{ $managedUser->id }}-heading">{{ __('Applicant profile') }}</h3>
    </header>

    <section class="nw-admin-applicant-profile__section">
        <h4>{{ __('Contact information and introduction') }}</h4>
        <dl class="nw-detail-list">
            <div><dt>{{ __('Date of birth') }}</dt><dd>{{ $managedUser->date_of_birth?->format('j M Y') ?: '—' }}</dd></div>
            <div><dt>{{ __('Address') }}</dt><dd>{{ $managedUser->address ?: '—' }}</dd></div>
            <div><dt>{{ __('Gender identity') }}</dt><dd>{{ $genderLabel }}</dd></div>
            <div><dt>{{ __('Disability status') }}</dt><dd>{{ $disabilityLabel }}</dd></div>
            @if ($identity?->disability_status === 'yes')
                <div><dt>{{ __('Disability details') }}</dt><dd>{{ $identity->disability_details ?: '—' }}</dd></div>
            @endif
            <div><dt>{{ __('Professional headline') }}</dt><dd>{{ $applicantProfile?->professional_headline ?: '—' }}</dd></div>
            <div class="nw-admin-applicant-profile__multiline"><dt>{{ __('About you') }}</dt><dd>{{ $applicantProfile?->about ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="nw-admin-applicant-profile__section">
        <h4>{{ __('Employment') }}</h4>
        @forelse ($applicantProfile?->employments ?? [] as $employment)
            <article class="nw-admin-applicant-profile__entry">
                <div class="nw-admin-applicant-profile__entry-heading">
                    <strong>{{ $employment->title }}</strong>
                    <span>{{ $employment->company }}</span>
                </div>
                <dl class="nw-detail-list">
                    <div><dt>{{ __('Work location') }}</dt><dd>{{ $employment->location ?: '—' }}</dd></div>
                    <div><dt>{{ __('Employment type') }}</dt><dd>{{ $employment->employment_type ? ($employmentTypes[$employment->employment_type] ?? $employment->employment_type) : '—' }}</dd></div>
                    <div><dt>{{ __('Start date') }}</dt><dd>{{ $employment->start_date?->format('j M Y') ?: '—' }}</dd></div>
                    <div><dt>{{ __('End date') }}</dt><dd>{{ $employment->is_current ? __('Current role') : ($employment->end_date?->format('j M Y') ?: '—') }}</dd></div>
                </dl>
                @if ($employment->description)
                    <p class="nw-admin-applicant-profile__multiline">{{ $employment->description }}</p>
                @endif
            </article>
        @empty
            <p class="nw-admin-applicant-profile__empty">{{ __('No employment details added.') }}</p>
        @endforelse

        <h5>{{ __('Skills') }}</h5>
        @if ($applicantProfile?->skills?->isNotEmpty())
            <ul class="nw-admin-applicant-profile__tags">
                @foreach ($applicantProfile->skills as $skill)
                    <li>{{ $skill->name }}</li>
                @endforeach
            </ul>
        @else
            <p class="nw-admin-applicant-profile__empty">{{ __('No skills added.') }}</p>
        @endif
    </section>

    <section class="nw-admin-applicant-profile__section">
        <h4>{{ __('Education') }}</h4>
        @forelse ($applicantProfile?->educations ?? [] as $education)
            <article class="nw-admin-applicant-profile__entry">
                <div class="nw-admin-applicant-profile__entry-heading">
                    <strong>{{ $education->qualification }}</strong>
                    <span>{{ $education->institution }}</span>
                </div>
                <dl class="nw-detail-list">
                    <div><dt>{{ __('Field of study') }}</dt><dd>{{ $education->field_of_study ?: '—' }}</dd></div>
                    <div><dt>{{ __('Dates') }}</dt><dd>{{ $education->start_date?->format('Y') ?: '—' }} – {{ $education->end_date?->format('Y') ?: __('Present') }}</dd></div>
                </dl>
                @if ($education->description)
                    <p class="nw-admin-applicant-profile__multiline">{{ $education->description }}</p>
                @endif
            </article>
        @empty
            <p class="nw-admin-applicant-profile__empty">{{ __('No education details added.') }}</p>
        @endforelse
    </section>

    <section class="nw-admin-applicant-profile__section">
        <h4>{{ __('Online profiles') }}</h4>
        @if ($applicantProfile?->links?->isNotEmpty())
            <ul class="nw-admin-applicant-profile__links">
                @foreach ($applicantProfile->links as $link)
                    <li>
                        <span>{{ ucfirst($link->platform) }}</span>
                        @if (str_starts_with(strtolower($link->url), 'https://') || str_starts_with(strtolower($link->url), 'http://'))
                            <a class="nw-link" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">{{ $link->url }}</a>
                        @else
                            <span>{{ $link->url }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="nw-admin-applicant-profile__empty">{{ __('No online profiles added.') }}</p>
        @endif
    </section>

    <section class="nw-admin-applicant-profile__section">
        <h4>{{ __('Work preferences') }}</h4>
        <dl class="nw-detail-list">
            <div><dt>{{ __('Preferred roles') }}</dt><dd>{{ collect($preferences?->preferred_roles ?? [])->implode(', ') ?: '—' }}</dd></div>
            <div><dt>{{ __('Preferred locations') }}</dt><dd>{{ collect($preferences?->preferred_locations ?? [])->implode(', ') ?: '—' }}</dd></div>
            <div><dt>{{ __('Employment type') }}</dt><dd>{{ $preferences?->preferred_job_type ? ($employmentTypes[$preferences->preferred_job_type] ?? $preferences->preferred_job_type) : '—' }}</dd></div>
            <div><dt>{{ __('Preferred work style') }}</dt><dd>{{ $preferences?->preferred_workplace ? ($workplaceLabels[$preferences->preferred_workplace] ?? $preferences->preferred_workplace) : '—' }}</dd></div>
            <div><dt>{{ __('Salary range') }}</dt><dd>@if ($preferences?->salary_min !== null || $preferences?->salary_max !== null){{ $preferences?->salary_min !== null ? number_format($preferences->salary_min) : '—' }} – {{ $preferences?->salary_max !== null ? number_format($preferences->salary_max) : '—' }}@else—@endif</dd></div>
            <div><dt>{{ __('Job alert frequency') }}</dt><dd>{{ $preferences?->job_alert_frequency?->label() ?? '—' }}</dd></div>
            <div><dt>{{ __('Visible to employers') }}</dt><dd>{{ $preferences ? ($preferences->profile_visible_to_employers ? __('Yes') : __('No')) : '—' }}</dd></div>
        </dl>
    </section>

    @include('admin.users.partials.document-previewer', ['documents' => $adminApplicantDocuments])
</section>
