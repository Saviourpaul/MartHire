@php
    $stages = \App\Enums\CandidatePipelineStage::cases();
@endphp
<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Candidates') }}</h1>
        <p>{{ __('Review and move candidates through your recruitment pipeline.') }}</p>
    </x-slot>

    @fragment('organization-candidates')
    <div data-live-table="organization-candidates" data-live-topics="candidates,applications,interviews,jobs,team" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
    <form method="GET" action="{{ route($routeName) }}" role="search" data-live-filter>
        @if ($statusFilter)
            <input type="hidden" name="stage" value="{{ $statusFilter->value }}">
        @endif
        <x-table.toolbar :tabs-label="__('Pipeline stage')">
            <x-slot:tabs>
                <a class="nw-segmented__item{{ ! $statusFilter ? ' is-active' : '' }}" href="{{ route($routeName, request()->except('page', 'stage')) }}" data-live-link>{{ __('All') }}</a>
                @foreach ($stages as $stage)
                    <a class="nw-segmented__item{{ $statusFilter === $stage ? ' is-active' : '' }}" href="{{ route($routeName, array_merge(request()->except('page', 'stage'), ['stage' => $stage->value])) }}" data-live-link>
                        {{ $stage->label() }}
                    </a>
                @endforeach
            </x-slot:tabs>

            <div class="nw-search-field">
                <x-icon name="search" />
                <label class="nw-sr-only" for="candidate-search">{{ __('Search candidates') }}</label>
                <input id="candidate-search" class="nw-input" type="search" name="search" placeholder="{{ __('Search candidates…') }}" value="{{ request('search') }}">
            </div>

            <x-slot:filters>
                <select class="nw-select" name="job_id" aria-label="{{ __('Filter by job') }}">
                    <option value="">{{ __('All jobs') }}</option>
                    @foreach ($jobs as $job)
                        <option value="{{ $job->id }}" @selected((string) request('job_id') === (string) $job->id)>{{ $job->title }}</option>
                    @endforeach
                </select>
            </x-slot:filters>

            <x-slot:actions>
                <button type="submit" class="nw-button nw-button--outline nw-button--small">{{ __('Filter') }}</button>
                @if ($exportRoute)
                    <a href="{{ route($exportRoute, request()->query()) }}" class="nw-button nw-button--outline nw-button--small">
                        <x-icon name="download" /> {{ __('Export') }}
                    </a>
                @endif
            </x-slot:actions>
        </x-table.toolbar>
    </form>

    <section class="nw-panel" aria-labelledby="candidates-heading">
        <header class="nw-panel__head nw-sr-only">
            <h2 id="candidates-heading">{{ $title }}</h2>
        </header>

        @if ($applications->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No candidates here yet.') }}</h3>
                <p>{{ __('Candidates will show up here once they apply, or move into this stage.') }}</p>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Candidate') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Stage') }}</th>
                            <th scope="col">{{ __('Documents') }}</th>
                            <th scope="col">{{ __('Applied') }}</th>
                            <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <div>
                                            <strong>{{ trim(($application->applicant?->first_name ?? '').' '.($application->applicant?->last_name ?? '')) }}</strong>
                                            <small>{{ $application->applicant?->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $application->job?->title }}</td>
                                <td><x-status-badge :status="$application->status" /></td>
                                <td>{{ $application->documents_count }}</td>
                                <td>{{ $application->submitted_at?->format('j M Y') }}</td>
                                <td class="nw-table__go nw-table__go--persist">
                                    <a class="nw-link nw-link--small" href="{{ route('organisations.candidates.show', $application) }}">{{ __('Review') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nw-pagination">{{ $applications->onEachSide(1)->links() }}</div>
        @endif
    </section>
    </div>
    @endfragment
</x-app-layout>
