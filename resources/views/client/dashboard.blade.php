<x-app-layout>
    <x-slot name="title">{{ __('Your dashboard') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Welcome back') }}</h1>
        <p>{{ __('Track your applications and find your next role.') }}</p>
    </x-slot>

    @fragment('client-dashboard')
    <div data-live-table="client-dashboard" data-live-topics="users,jobs,candidates,applications,team,interviews" data-live-pages="applications_page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
    @if ($profileCompletion < 100)
        <div class="nw-notice" role="status">
            {{ __('Your profile is :percent% complete.', ['percent' => $profileCompletion]) }}
            <a class="nw-link" href="{{ route('profile.edit') }}">{{ __('Finish your profile') }}</a>
        </div>
    @endif

    <section id="applications" class="nw-panel" aria-labelledby="applications-heading">
        <header class="nw-panel__head">
            <div>
                <h2 id="applications-heading">{{ __('Your applications') }}</h2>
                <p>{{ __('The roles you\'ve applied for, newest first.') }}</p>
            </div>
            <a class="nw-link nw-link--small" href="{{ route('workable-jobs.index') }}">{{ __('Browse jobs') }} <x-icon name="arrow-up-right" /></a>
        </header>

        @if ($applications->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No applications yet.') }}</h3>
                <p>{{ __('When you apply for a role, you\'ll be able to follow it here.') }}</p>
                <a class="nw-button nw-button--primary" href="{{ route('workable-jobs.index') }}">{{ __('Find a role') }} <x-icon name="arrow-up-right" /></a>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Applied') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <div>
                                            <strong>{{ $application->job?->title }}</strong>
                                            <small>{{ $application->job?->company }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $application->submitted_at?->format('j M Y') }}</td>
                                <td><x-status-badge :status="$application->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="nw-pagination">{{ $applications->onEachSide(1)->links() }}</div>
        @endif
    </section>

    <section id="recommended" class="nw-dash-section" aria-labelledby="recommended-heading">
        <div class="nw-dash-section__head">
            <div>
                <h2 id="recommended-heading">{{ __('New roles to look at') }}</h2>
                <p>{{ __('Fresh listings you haven\'t applied for yet.') }}</p>
            </div>
            <a class="nw-link" href="{{ route('workable-jobs.index') }}">{{ __('Browse all jobs') }} <x-icon name="arrow-right" /></a>
        </div>

        @if ($suggestedJobs->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No open roles right now.') }}</h3>
                <p>{{ __('Check back soon — new roles are posted regularly.') }}</p>
            </div>
        @else
            <div class="nw-job-grid">
                @foreach ($suggestedJobs as $job)
                    <article class="nw-job">
                        <header class="nw-job__top">
                            <div>
                                <p class="nw-job__company">{{ $job->company }}</p>
                            </div>
                        </header>
                        <h3 class="nw-job__title">{{ $job->title }}</h3>
                        <ul class="nw-job__meta" aria-label="{{ __('Role details') }}">
                            @if ($job->location)
                                <li><x-icon name="map-pin" /> {{ $job->location }}</li>
                            @endif
                            @if ($job->employment_type)
                                <li><x-icon name="briefcase" /> {{ $job->employment_type }}</li>
                            @endif
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    </div>
    @endfragment
</x-app-layout>
