@php
    $statusTabs = [
        'All Applied' => 'employer.Applied-Candidates',
        'Approved' => 'employer.Approved-Candidates',
        'Rejected' => 'employer.Rejected-Candidate',
    ];

    $statusClass = function (string $status): string {
        return match (strtolower($status)) {
            'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            'rejected' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
            default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
        };
    };
@endphp

    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Review and manage applications for your posted jobs.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
                    @foreach ($statusTabs as $label => $tabRoute)
                        <a href="{{ route($tabRoute) }}" class="rounded-md px-3 py-2 text-theme-sm font-medium transition {{ request()->routeIs($tabRoute) ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)" class="mb-6 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                    Please review the request and try again.
                </div>
            @endif

            <div data-table-refresh-region data-table-refresh-id="employer-candidates">
            <section class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <form action="{{ route($routeName) }}" method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-5">
                        <label for="search" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Search</label>
                        <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Candidate, job, company, or reference" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="lg:col-span-4">
                        <label for="job_id" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Job</label>
                        <select id="job_id" name="job_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">All jobs</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" @selected((string) request('job_id') === (string) $job->id)>{{ $job->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3 lg:col-span-3">
                        <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 sm:flex-none">Filter</button>
                        <a href="{{ route($routeName) }}" data-table-reset-link class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 sm:flex-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4 flex flex-col gap-2 px-6 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Applications</h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ number_format($applications->total()) }} total</p>
                        <button type="button" data-table-refresh-button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <svg data-table-refresh-icon class="fill-current" width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.75 6.667a.75.75 0 0 1-.75.75h-3.333a.75.75 0 0 1 0-1.5h1.516A5.25 5.25 0 0 0 5.02 7.553a.75.75 0 1 1-1.372-.606 6.75 6.75 0 0 1 11.602-1.9V3.75a.75.75 0 0 1 1.5 0v2.917ZM3.25 13.333a.75.75 0 0 1 .75-.75h3.333a.75.75 0 0 1 0 1.5H5.817a5.25 5.25 0 0 0 9.163-1.636.75.75 0 1 1 1.372.606 6.75 6.75 0 0 1-11.602 1.9v1.297a.75.75 0 0 1-1.5 0v-2.917Z"/></svg>
                            <span data-table-refresh-label>Reload</span>
                        </button>
                    </div>
                </div>
                <div data-table-refresh-message class="mx-6 mb-4 hidden rounded-lg border px-4 py-3 text-theme-sm font-medium"></div>

                <div class="custom-scrollbar max-w-full overflow-x-auto">
                    <table class="w-full min-w-[980px]">
                        <thead>
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Candidate</p></th>
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Job</p></th>
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Reference</p></th>
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Submitted</p></th>
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Documents</p></th>
                                <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                                <th class="px-6 py-3 text-right"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $application)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-6 py-3.5">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $application->first_name }} {{ $application->last_name }}</p>
                                        <p class="text-gray-500 text-theme-xs dark:text-gray-400">{{ $application->email }}</p>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $application->job->title }}</p>
                                        <p class="text-gray-500 text-theme-xs dark:text-gray-400">{{ $application->job->company }}</p>
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->reference }}</td>
                                    <td class="px-6 py-3.5 text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->submitted_at?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-6 py-3.5 text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->approved_documents_count }}/{{ $application->documents_count }} approved</td>
                                    <td class="px-6 py-3.5"><span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($application->status->label()) }}">{{ $application->status->label() }}</span></td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('employer.applications.show', $application) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">View</a>

                                            @if ($application->status !== \App\Enums\ApplicationStatus::Approved)
                                                <form action="{{ route('employer.applications.review', $application) }}" method="POST" data-confirm data-confirm-title="Approve application?" data-confirm-text="The applicant will be marked as approved." data-confirm-button="Approve">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-success-500 px-3 text-theme-xs font-medium text-white shadow-theme-xs hover:bg-success-600">Approve</button>
                                                </form>
                                            @endif

                                            @if ($application->status !== \App\Enums\ApplicationStatus::Rejected)
                                                <form action="{{ route('employer.applications.review', $application) }}" method="POST" data-confirm data-confirm-title="Reject application?" data-confirm-text="The applicant will be marked as rejected." data-confirm-button="Reject">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-error-500 px-3 text-theme-xs font-medium text-white shadow-theme-xs hover:bg-error-600">Reject</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td colspan="7" class="px-6 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                    {{ $applications->links() }}
                </div>
            </section>
            </div>
        </div>
    </main>
