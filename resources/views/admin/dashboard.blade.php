<x-app-layout>
    <x-slot name="title">{{ __('Admin dashboard') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Admin dashboard') }}</h1>
        <p>{{ __('Platform-wide activity across organizations, applicants, and jobs.') }}</p>
    </x-slot>

    <div class="nw-stats">
        <div class="nw-stat">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Applicants') }}</span>
                <span class="nw-stat__icon"><x-icon name="user" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_applicants']) }}</span>
        </div>
        <div class="nw-stat nw-stat--lime">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Organizations') }}</span>
                <span class="nw-stat__icon"><x-icon name="briefcase" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_organizations']) }}</span>
        </div>
        <div class="nw-stat nw-stat--peach">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Jobs posted') }}</span>
                <span class="nw-stat__icon"><x-icon name="grid" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_jobs']) }}</span>
        </div>
        <div class="nw-stat nw-stat--ink">
            <div class="nw-stat__top">
                <span class="nw-stat__label">{{ __('Applications') }}</span>
                <span class="nw-stat__icon"><x-icon name="file" /></span>
            </div>
            <span class="nw-stat__value">{{ number_format($metrics['total_applications']) }}</span>
        </div>
    </div>

    <div class="nw-dash-grid">
        <section class="nw-panel" aria-labelledby="recent-users-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="recent-users-heading">{{ __('Newest accounts') }}</h2>
                    <p>{{ __('The latest applicants and organizations to join.') }}</p>
                </div>
            </header>

            @if ($recentActivities['users']->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No accounts yet.') }}</h3>
                </div>
            @else
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Email') }}</th>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Joined') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivities['users'] as $recentUser)
                                <tr>
                                    <td class="nw-table__main">{{ trim($recentUser->first_name.' '.$recentUser->last_name) }}</td>
                                    <td>{{ $recentUser->email }}</td>
                                    <td><x-status-badge :status="$recentUser->role->value" /></td>
                                    <td>{{ $recentUser->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="nw-panel" aria-labelledby="recent-jobs-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="recent-jobs-heading">{{ __('Newest jobs') }}</h2>
                    <p>{{ __('The latest roles posted on the platform.') }}</p>
                </div>
            </header>

            @if ($recentActivities['jobs']->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No jobs yet.') }}</h3>
                </div>
            @else
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Title') }}</th>
                                <th scope="col">{{ __('Company') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Posted') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivities['jobs'] as $recentJob)
                                <tr>
                                    <td class="nw-table__main">{{ $recentJob->title }}</td>
                                    <td>{{ $recentJob->company }}</td>
                                    <td><x-status-badge :status="$recentJob->status->value" /></td>
                                    <td>{{ $recentJob->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="nw-panel" aria-labelledby="recent-applications-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="recent-applications-heading">{{ __('Newest applications') }}</h2>
                    <p>{{ __('The latest applications submitted across all organizations.') }}</p>
                </div>
            </header>

            @if ($recentActivities['applications']->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No applications yet.') }}</h3>
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
                            @foreach ($recentActivities['applications'] as $application)
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
    </div>
</x-app-layout>
