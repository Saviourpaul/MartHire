<x-app-layout>
    <x-slot name="title">{{ __('Organization dashboard') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ $organization?->name ?? __('Your organization') }}</h1>
        <p>{{ __('Recruitment activity for your organization.') }}</p>
    </x-slot>

    <div class="nw-notice" role="status">
        {{ __('Signed in as :role', ['role' => $membership->role->value]) }}
    </div>

    <div class="nw-stats">
        <div class="nw-stat">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Open jobs') }}</span>
                <span class="nw-stat__icon"><x-icon name="briefcase" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_jobs']) }}</span>
        </div>
        <div class="nw-stat nw-stat--lime">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Applicants') }}</span>
                <span class="nw-stat__icon"><x-icon name="user" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_applicants']) }}</span>
        </div>
        <div class="nw-stat nw-stat--peach">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Applications') }}</span>
                <span class="nw-stat__icon"><x-icon name="file" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_applications']) }}</span>
        </div>
        <div class="nw-stat nw-stat--ink">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Selected candidates') }}</span>
                <span class="nw-stat__icon"><x-icon name="check" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['selected_candidates']) }}</span>
        </div>
    </div>

    <div class="nw-dash-grid">
        <section class="nw-panel" aria-labelledby="applications-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="applications-heading">{{ __('Recent applications') }}</h2>
                    <p>{{ __('The newest applications across your organization\'s jobs.') }}</p>
                </div>
            </header>

            @if ($recentApplications->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No applications yet.') }}</h3>
                    <p>{{ __('Applications will show up here as candidates apply to your jobs.') }}</p>
                </div>
            @else
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Applicant') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Submitted') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentApplications as $application)
                                <tr>
                                    <td class="nw-table__main">{{ $application->job?->title }}</td>
                                    <td>{{ trim(($application->applicant?->first_name ?? '').' '.($application->applicant?->last_name ?? '')) }}</td>
                                    <td><x-status-badge :status="$application->status" /></td>
                                    <td>{{ $application->submitted_at?->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="nw-dash-stack">
            <section class="nw-panel" aria-labelledby="most-applied-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="most-applied-heading">{{ __('Most applied-for jobs') }}</h2>
                    </div>
                </header>

                @if (empty($mostAppliedJobs))
                    <div class="nw-empty">
                        <h3>{{ __('No applications yet.') }}</h3>
                    </div>
                @else
                    <ul class="nw-ranked-list">
                        @foreach ($mostAppliedJobs as $job)
                            <li>
                                <strong>{{ $job['title'] }}</strong>
                                <span>{{ trans_choice(':count application|:count applications', $job['applications_count'], ['count' => $job['applications_count']]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="nw-panel" aria-labelledby="permissions-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="permissions-heading">{{ __('Your permissions') }}</h2>
                        <p>{{ __('What your :role role can do in this organization.', ['role' => $membership->role->value]) }}</p>
                    </div>
                </header>

                <ul class="nw-permissions-list">
                    @foreach (['manage_team' => __('Manage team members'), 'hiring_settings' => __('Manage organization settings'), 'create_jobs' => __('Post new jobs'), 'delete_jobs' => __('Delete jobs'), 'select_candidates' => __('Select candidates'), 'send_candidate_email' => __('Email candidates')] as $key => $label)
                        <li class="{{ in_array($key, $permissions, true) ? 'is-allowed' : 'is-denied' }}">
                            <x-icon :name="in_array($key, $permissions, true) ? 'check' : 'close'" />
                            {{ $label }}
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
