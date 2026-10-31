<x-app-layout>
    <x-slot name="title">{{ __('Jobs') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Jobs') }}</h1>
        <p>{{ __('Post and manage your organization’s roles.') }}</p>
    </x-slot>

    @fragment('organization-jobs')
    <div data-live-table="organization-jobs" data-live-topics="jobs,applications,candidates,team" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" role="search" data-live-filter>
        <x-table.toolbar>
            <div class="nw-search-field">
                <x-icon name="search" />
                <label class="nw-sr-only" for="job-search">{{ __('Search jobs') }}</label>
                <input id="job-search" class="nw-input" type="search" name="search" placeholder="{{ __('Search jobs…') }}" value="{{ request('search') }}">
            </div>
            <x-slot:filters>
                <select class="nw-select" name="status" aria-label="{{ __('Filter jobs by status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\JobStatus::cases() as $jobStatus)
                        <option value="{{ $jobStatus->value }}" @selected(request('status') === $jobStatus->value)>{{ $jobStatus->label() }}</option>
                    @endforeach
                </select>
                <select class="nw-select" name="sort" aria-label="{{ __('Sort jobs by') }}">
                    @foreach (['created_at' => __('Created'), 'title' => __('Title'), 'due_date' => __('Deadline')] as $column => $label)
                        <option value="{{ $column }}" @selected($sortColumn === $column)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="nw-select" name="direction" aria-label="{{ __('Sort direction') }}">
                    <option value="desc" @selected($sortDirection === 'desc')>{{ __('Descending') }}</option>
                    <option value="asc" @selected($sortDirection === 'asc')>{{ __('Ascending') }}</option>
                </select>
            </x-slot:filters>
            <x-slot:actions>
                <button type="submit" class="nw-button nw-button--outline nw-button--small">{{ __('Filter') }}</button>
                @if (request()->filled('search') || request()->filled('status'))
                    <a href="{{ route('organisations.jobs.index') }}" class="nw-link nw-link--small" data-live-link>{{ __('Clear') }}</a>
                @endif
                <a href="{{ route('organisations.jobs.create') }}" class="nw-button nw-button--primary nw-button--small">
                    <x-icon name="briefcase" /> {{ __('New job') }}
                </a>
            </x-slot:actions>
        </x-table.toolbar>
    </form>

    <section class="nw-panel" aria-labelledby="jobs-heading">
        <header class="nw-panel__head nw-sr-only">
            <h2 id="jobs-heading">{{ __('Your jobs') }}</h2>
        </header>

        @if ($jobs->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No jobs yet.') }}</h3>
                <p>{{ __('Post your first role to start receiving applications.') }}</p>
                <a class="nw-button nw-button--primary" href="{{ route('organisations.jobs.create') }}">{{ __('Post a job') }}</a>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Applications') }}</th>
                            <th scope="col">{{ __('Deadline') }}</th>
                            <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jobs as $job)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <div>
                                            <strong><a class="nw-link" href="{{ route('organisations.jobs.show', $job) }}">{{ $job->title }}</a></strong>
                                            <small>{{ $job->company }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><x-status-badge :status="$job->status" /></td>
                                <td>{{ $job->applications_count }}</td>
                                <td>{{ $job->due_date?->format('j M Y') }}</td>
                                <td class="nw-table__go nw-table__go--persist">
                                    @can('update', $job)
                                        <a class="nw-link nw-link--small" href="{{ route('organisations.jobs.edit', $job) }}">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $job)
                                        <form method="POST" action="{{ route('organisations.jobs.destroy', $job) }}" data-confirm="{{ __('Delete this job? This cannot be undone.') }}" data-confirm-label="{{ __('Delete job') }}" data-confirm-type="error" class="nw-inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="nw-button nw-button--danger nw-button--small">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nw-pagination">{{ $jobs->onEachSide(1)->links() }}</div>
        @endif
    </section>
    </div>
    @endfragment
</x-app-layout>
