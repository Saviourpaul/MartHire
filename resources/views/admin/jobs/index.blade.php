<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    @fragment('admin-jobs')
    <div data-live-table="admin-jobs" data-live-topics="jobs,applications,candidates" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
        <header class="nw-page-heading nw-page-heading--app">
            <div>
                <h1 class="nw-heading">{{ $title }}</h1>
                <p>{{ __('Review published organization jobs and moderate their public availability.') }}</p>
            </div>
        </header>

        <form method="GET" action="{{ request()->url() }}" role="search" data-live-filter>
            <x-table.toolbar :tabs-label="__('Job status')">
                <x-slot:tabs>
                    <a class="nw-segmented__item{{ request()->routeIs('admin.jobs.index') ? ' is-active' : '' }}" href="{{ route('admin.jobs.index') }}" data-live-link>{{ __('All jobs') }}</a>
                    <a class="nw-segmented__item{{ request()->routeIs('admin.jobs.open') ? ' is-active' : '' }}" href="{{ route('admin.jobs.open') }}" data-live-link>{{ __('Open') }}</a>
                    <a class="nw-segmented__item{{ request()->routeIs('admin.jobs.closed') ? ' is-active' : '' }}" href="{{ route('admin.jobs.closed') }}" data-live-link>{{ __('Closed') }}</a>
                    <a class="nw-segmented__item{{ request()->routeIs('admin.jobs.suspended') ? ' is-active' : '' }}" href="{{ route('admin.jobs.suspended') }}" data-live-link>{{ __('Suspended') }}</a>
                </x-slot:tabs>

                <div class="nw-search-field">
                    <x-icon name="search" />
                    <label class="nw-sr-only" for="job-search">{{ __('Search jobs') }}</label>
                    <input id="job-search" class="nw-input" type="search" name="search" maxlength="120" placeholder="{{ __('Search title, organization or employer…') }}" value="{{ $filterValues['search'] }}">
                </div>

                <x-slot:filters>
                    @unless ($statusConstraint)
                        <select class="nw-select" name="status" aria-label="{{ __('Filter by status') }}">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($filterValues['status'] === $statusOption->value)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                    @endunless
                    <select class="nw-select" name="category" aria-label="{{ __('Filter by category') }}">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $categoryOption)
                            <option value="{{ $categoryOption }}" @selected($filterValues['category'] === $categoryOption)>{{ $categoryOption }}</option>
                        @endforeach
                    </select>
                    <select class="nw-select" name="sort" aria-label="{{ __('Sort jobs by') }}">
                        @foreach (['created_at' => __('Date added'), 'status' => __('Status'), 'title' => __('Title'), 'company' => __('Organization'), 'category' => __('Category'), 'employer' => __('Employer')] as $column => $label)
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
                    @if ($filterValues['search'] || $filterValues['status'] || $filterValues['category'])
                        <a href="{{ request()->url() }}" class="nw-link nw-link--small" data-live-link>{{ __('Clear') }}</a>
                    @endif
                </x-slot:actions>
            </x-table.toolbar>
        </form>

        @if ($errors->any())
            <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="nw-panel" aria-labelledby="admin-jobs-heading">
            <header class="nw-panel__head nw-sr-only">
                <h2 id="admin-jobs-heading">{{ $title }}</h2>
            </header>

            @if ($jobs->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No jobs found.') }}</h3>
                    <p>{{ __('Try a different search or filter.') }}</p>
                </div>
            @else
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Job') }}</th>
                                <th scope="col">{{ __('Employer') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Applications') }}</th>
                                <th scope="col">{{ __('Deadline') }}</th>
                                <th scope="col">{{ __('Added') }}</th>
                                <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                <tr>
                                    <td class="nw-table__main">
                                        <div>
                                            <strong><a class="nw-link" href="{{ route('admin.jobs.show', $job) }}">{{ $job->title }}</a></strong>
                                            <small>{{ $job->organization?->name ?? $job->company }}{{ $job->category ? ' · '.$job->category : '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($job->employer)
                                            <span>{{ trim($job->employer->first_name.' '.$job->employer->last_name) ?: $job->employer->email }}</span>
                                            <small>{{ $job->employer->email }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <x-status-badge :status="$job->status" />
                                    </td>
                                    <td>{{ number_format($job->applications_count) }}</td>
                                    <td>{{ $job->due_date?->format('j M Y') ?? '—' }}</td>
                                    <td>{{ $job->created_at?->format('j M Y') }}</td>
                                    <td class="nw-table__go nw-table__go--persist">
                                        <a class="nw-link nw-link--small" href="{{ route('admin.jobs.show', $job) }}">{{ __('Review') }}</a>
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
