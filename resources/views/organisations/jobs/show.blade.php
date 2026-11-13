<x-app-layout>
    <x-slot name="title">{{ $job->title }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ $job->title }}</h1>
        <p>{{ $job->company }}</p>
    </x-slot>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="nw-form-bar" style="margin-bottom:20px">
        <div class="nw-form-bar__grow">
            <x-status-badge :status="$job->status" />
        </div>
     
    </div>

    <section class="nw-panel">
        <dl class="nw-detail-list">
            <div><dt>{{ __('Country') }}</dt><dd>
                @if ($country)
                    <x-job-country :job="['country' => $country['name'], 'flag_url' => $country['flag_url'], 'flag_emoji' => $country['flag_emoji']]" />
                @else
                    {{ $job->country_code ?: '—' }}
                @endif
            </dd></div>
            <div><dt>{{ __('Employment type') }}</dt><dd>{{ \App\Models\Job::employmentTypeOptions()[$job->employment_type] ?? $job->employment_type }}</dd></div>
            <div><dt>{{ __('Category') }}</dt><dd>{{ $job->category ?: '—' }}</dd></div>
            <div><dt>{{ __('Start date') }}</dt><dd>{{ $job->start_date?->format('j M Y') }}</dd></div>
            <div><dt>{{ __('Deadline') }}</dt><dd>{{ $job->due_date?->format('j M Y') }}</dd></div>
            <div><dt>{{ __('Applications') }}</dt><dd>{{ $job->applications_count ?? $job->applications()->count() }}</dd></div>
        </dl>

        <div class="nw-rich-content">
            {!! app(\App\Support\JobDescription::class)->toHtml($job->description) !!}
        </div>
    </section>

    @can('manageTeam', $job)
        <section class="nw-panel nw-job-team" aria-labelledby="job-team-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="job-team-heading">{{ __('Hiring team') }}</h2>
                    <p>{{ __('Choose which active organization members can access this job.') }}</p>
                </div>
                <a class="nw-link nw-link--small" href="{{ route('organisations.team.index') }}">{{ __('Manage organization team') }}</a>
            </header>

            @if ($availableTeamMembers->isEmpty())
                <div class="nw-empty nw-job-team__empty">
                    <p>{{ __('There are no active teammates to assign yet.') }}</p>
                    <a class="nw-button nw-button--outline nw-button--small" href="{{ route('organisations.team.index') }}">{{ __('Invite a teammate') }}</a>
                </div>
            @else
                <form method="POST" action="{{ route('organisations.jobs.team.update', $job) }}" class="nw-job-team__form">
                    @csrf
                    @method('PATCH')

                    <fieldset class="nw-job-team__list">
                        <legend class="nw-sr-only">{{ __('Assign organization members to this job') }}</legend>
                        @foreach ($availableTeamMembers as $member)
                            <label class="nw-job-team__member">
                                <input class="nw-checkbox" type="checkbox" name="user_ids[]" value="{{ $member->user_id }}" @checked(in_array($member->user_id, $assignedUserIds, true))>
                                <span class="nw-job-team__identity">
                                    <strong>{{ trim($member->user->first_name.' '.$member->user->last_name) ?: $member->user->email }}</strong>
                                    <small>{{ $member->user->email }}</small>
                                </span>
                                <span class="nw-job-team__role">{{ $member->role->label() }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    @error('user_ids') <p class="nw-field__error" role="alert">{{ $message }}</p> @enderror
                    @error('user_ids.*') <p class="nw-field__error" role="alert">{{ $message }}</p> @enderror

                    <div class="nw-job-team__actions">
                        <span class="nw-hint">{{ __('Unselected teammates will lose access to this job.') }}</span>
                        <button type="submit" class="nw-button nw-button--primary">{{ __('Save hiring team') }}</button>
                    </div>
                </form>
            @endif
        </section>
    @endcan
</x-app-layout>
