<x-layout>
    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => $sortColumn === $column && $sortDirection === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);
        $sortOptions = [
            'created_at' => 'Submitted date',
            'title' => 'Job title',
            'employer' => 'Employer',
            'company' => 'Company',
            'category' => 'Category',
            'status' => 'Job status',
        ];
    @endphp
    <main class="mx-auto w-full min-w-0 max-w-7xl p-4 pb-20 sm:p-6">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Review employer job content and moderate policy violations.</p>
        </div>
        @if (session('success'))
            <div role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ session('error') ?: $errors->first() }}</div>
        @endif

        <section data-table-refresh-region data-table-refresh-id="admin-jobs" class="min-w-0 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('admin.jobs.index') }}" aria-label="Filter and sort job listings" class="grid min-w-0 grid-cols-1 gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <label class="min-w-0 text-sm font-medium text-gray-700 sm:col-span-2 xl:col-span-3 2xl:col-span-2 dark:text-gray-200">Search
                    <input type="search" name="search" maxlength="120" value="{{ $filterValues['search'] }}" placeholder="Title, employer, category…" class="mt-2 block min-h-[44px] w-full min-w-0 rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:text-white">
                </label>
                <label class="min-w-0 text-sm font-medium text-gray-700 dark:text-gray-200">Category
                    <select name="category" class="mt-2 block min-h-[44px] w-full min-w-0 rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($filterValues['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="min-w-0 text-sm font-medium text-gray-700 dark:text-gray-200">Job status
                    <select name="status" class="mt-2 block min-h-[44px] w-full min-w-0 rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($statusConstraint?->value ?? $filterValues['status']) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="min-w-0 text-sm font-medium text-gray-700 dark:text-gray-200">Sort by
                    <select name="sort" class="mt-2 block min-h-[44px] w-full min-w-0 rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        @foreach ($sortOptions as $column => $label)
                            <option value="{{ $column }}" @selected($sortColumn === $column)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="min-w-0 text-sm font-medium text-gray-700 dark:text-gray-200">Sort direction
                    <select name="direction" class="mt-2 block min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="desc" @selected($sortDirection === 'desc')>Descending</option>
                        <option value="asc" @selected($sortDirection === 'asc')>Ascending</option>
                    </select>
                </label>
                <div class="flex min-w-0 flex-wrap items-end gap-2">
                    <button type="submit" class="min-h-[44px] rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filter</button>
                    <a href="{{ route('admin.jobs.index') }}" data-table-reset-link class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-600 dark:text-gray-200">Reset</a>
                </div>
            </form>
            <p data-table-refresh-message role="alert" aria-live="polite" class="mx-4 mb-4 hidden rounded-lg border px-4 py-3 text-sm"></p>
            <div role="region" aria-label="Employer job listings" tabindex="0" class="min-w-0 overflow-x-auto p-4 pt-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 lg:p-0">
                <table role="table" class="block w-full text-left text-sm lg:table lg:min-w-[900px] lg:table-fixed">
                    <caption class="sr-only">Employer jobs for content review and moderation</caption>
                    <thead role="rowgroup" class="hidden border-y border-gray-200 bg-gray-50 text-gray-600 lg:table-header-group dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr role="row">
                            @foreach (['title' => 'Job / company', 'employer' => 'Employer', 'status' => 'Job status', 'created_at' => 'Submitted'] as $column => $label)
                                <th id="admin-job-column-{{ $column }}" role="columnheader" scope="col" class="px-4 py-2" aria-sort="{{ $sortColumn === $column ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ $sortUrl($column) }}" class="inline-flex min-h-[44px] items-center gap-1">{{ $label }} @if ($sortColumn === $column)<span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</a></th>
                            @endforeach
                            <th id="admin-job-column-moderation" role="columnheader" scope="col" class="px-4 py-3">Moderation</th>
                            <th id="admin-job-column-actions" role="columnheader" scope="col" class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup" class="grid grid-cols-1 gap-4 lg:table-row-group lg:divide-y lg:divide-gray-200 dark:lg:divide-gray-700">
                        @forelse ($jobs as $job)
                            <tr role="row" data-job-row="{{ $job->id }}" class="grid min-w-0 grid-cols-1 gap-4 rounded-lg border border-gray-200 p-4 text-gray-700 [overflow-wrap:anywhere] sm:grid-cols-2 lg:table-row lg:rounded-none lg:border-0 lg:p-0 dark:border-gray-700 dark:text-gray-200">
                                <td role="cell" headers="admin-job-column-title" class="block min-w-0 sm:col-span-2 lg:table-cell lg:px-4 lg:py-4 lg:align-top">
                                    <span class="mb-1 block text-xs font-medium text-gray-500 lg:hidden dark:text-gray-400">Job / company</span>
                                    <a href="{{ route('admin.jobs.show', $job) }}" class="font-semibold text-blue-700 underline-offset-2 hover:underline dark:text-blue-400">{{ $job->title }}</a>
                                    <p class="mt-1">{{ $job->company }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $job->category ?: 'Uncategorized' }} · {{ $job->location ?: 'Location not specified' }}</p>
                                </td>
                                <td role="cell" headers="admin-job-column-employer" class="block min-w-0 lg:table-cell lg:px-4 lg:py-4 lg:align-top">
                                    <span class="mb-1 block text-xs font-medium text-gray-500 lg:hidden dark:text-gray-400">Employer</span>
                                    {{ $job->employer?->first_name }} {{ $job->employer?->last_name }}<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $job->employer?->email ?: 'Employer unavailable' }}</p>
                                </td>
                                <td role="cell" headers="admin-job-column-status" class="block min-w-0 lg:table-cell lg:px-4 lg:py-4 lg:align-top">
                                    <span class="mb-2 block text-xs font-medium text-gray-500 lg:hidden dark:text-gray-400">Job status</span>
                                    <span class="inline-block rounded-full px-2 py-1 text-xs {{ $job->status->badgeClass() }}">{{ $job->status->label() }}</span>
                                </td>
                                <td role="cell" headers="admin-job-column-created_at" class="block min-w-0 lg:table-cell lg:whitespace-nowrap lg:px-4 lg:py-4 lg:align-top">
                                    <span class="mb-1 block text-xs font-medium text-gray-500 lg:hidden dark:text-gray-400">Submitted</span>
                                    {{ $job->created_at?->format('M j, Y') }}
                                </td>
                                <td role="cell" headers="admin-job-column-moderation" class="block min-w-0 lg:table-cell lg:px-4 lg:py-4 lg:align-top">
                                    <span class="mb-1 block text-xs font-medium text-gray-500 lg:hidden dark:text-gray-400">Moderation</span>
                                    @if ($job->suspension_reason)
                                        <span class="font-medium text-amber-800 dark:text-amber-300">{{ $job->isSuspended() ? 'Suspended' : 'Previous suspension' }}</span>
                                        <p class="mt-2 whitespace-pre-line text-xs leading-5">{{ $job->suspension_reason }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $job->suspended_at?->format('M j, Y, g:i A') }}</p>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Not suspended</span>
                                    @endif
                                </td>
                                <td role="cell" headers="admin-job-column-actions" class="block min-w-0 border-t border-gray-200 pt-3 sm:col-span-2 lg:table-cell lg:border-0 lg:px-4 lg:py-4 lg:align-top dark:border-gray-700">
                                    <a href="{{ route('admin.jobs.show', $job) }}" class="mb-2 inline-flex min-h-[44px] items-center text-blue-700 hover:underline dark:text-blue-400">Review content<span class="sr-only"> for {{ $job->title }}</span></a>
                                    @include('admin.partials.job-moderation-actions', ['job' => $job])
                                </td>
                            </tr>
                        @empty
                            <tr role="row" class="block lg:table-row"><td role="cell" colspan="6" class="block px-4 py-12 text-center text-gray-500 lg:table-cell dark:text-gray-400">No jobs match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex min-w-0 flex-col gap-3 border-t border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <p>Showing {{ $jobs->firstItem() ?? 0 }}-{{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }}</p>
                @if ($jobs->currentPage() > $jobs->lastPage())
                    <nav role="navigation" aria-label="Job listings pagination">
                        <a href="{{ $jobs->url(1) }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Return to first page</a>
                    </nav>
                @elseif ($jobs->hasPages())
                    @php
                        $firstVisiblePage = max(1, $jobs->currentPage() - 1);
                        $lastVisiblePage = min($jobs->lastPage(), $jobs->currentPage() + 1);
                        $pageLinkClass = 'inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800';
                    @endphp
                    <nav role="navigation" aria-label="Job listings pagination" class="grid min-w-0 grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                        <p aria-current="page" class="col-span-2 text-center text-sm sm:order-2 lg:hidden">Page {{ $jobs->currentPage() }} of {{ $jobs->lastPage() }}</p>
                        @if ($jobs->onFirstPage())
                            <span aria-disabled="true" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400 sm:order-1 dark:border-gray-700">Previous</span>
                        @else
                            <a href="{{ $jobs->previousPageUrl() }}" rel="prev" class="{{ $pageLinkClass }} sm:order-1">Previous</a>
                        @endif
                        <div class="hidden min-w-0 flex-wrap justify-center gap-1 lg:order-2 lg:flex">
                            @if ($firstVisiblePage > 1)
                                <a href="{{ $jobs->url(1) }}" aria-label="Go to page 1" class="{{ $pageLinkClass }}">1</a>
                                @if ($firstVisiblePage > 2)<span aria-hidden="true" class="inline-flex min-h-[44px] items-center px-1">…</span>@endif
                            @endif
                            @foreach ($jobs->getUrlRange($firstVisiblePage, $lastVisiblePage) as $page => $url)
                                @if ($page === $jobs->currentPage())
                                    <span aria-current="page" aria-label="Page {{ $page }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-sm font-medium text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="{{ $pageLinkClass }}">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if ($lastVisiblePage < $jobs->lastPage())
                                @if ($lastVisiblePage < $jobs->lastPage() - 1)<span aria-hidden="true" class="inline-flex min-h-[44px] items-center px-1">…</span>@endif
                                <a href="{{ $jobs->url($jobs->lastPage()) }}" aria-label="Go to page {{ $jobs->lastPage() }}" class="{{ $pageLinkClass }}">{{ $jobs->lastPage() }}</a>
                            @endif
                        </div>
                        @if ($jobs->hasMorePages())
                            <a href="{{ $jobs->nextPageUrl() }}" rel="next" class="{{ $pageLinkClass }} sm:order-3">Next</a>
                        @else
                            <span aria-disabled="true" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400 sm:order-3 dark:border-gray-700">Next</span>
                        @endif
                    </nav>
                @endif
            </div>
        </section>
    </main>
</x-layout>
