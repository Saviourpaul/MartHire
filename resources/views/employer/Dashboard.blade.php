<x-layout>
    @php
        $formatNumber = fn ($value): string => number_format((int) $value);
        $statusClass = function (string $status): string {
            return match (strtolower($status)) {
                'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                'rejected' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            };
        };
        $initialAnalyticsPayload = [
            'categories' => $charts['jobsOverTime']['categories'],
            'series' => [
                ['name' => $charts['jobsOverTime']['label'], 'data' => $charts['jobsOverTime']['series']],
                ['name' => $charts['applicationsOverTime']['label'], 'data' => $charts['applicationsOverTime']['series']],
            ],
            'totals' => [
                'jobs' => array_sum($charts['jobsOverTime']['series']),
                'applicants' => array_sum($charts['applicationsOverTime']['series']),
            ],
            'applicationStatus' => $charts['applicationStatus'],
        ];
        $initialAnalyticsPayload['empty'] = ($initialAnalyticsPayload['totals']['jobs'] + $initialAnalyticsPayload['totals']['applicants']) === 0;
    @endphp

    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="flex flex-col justify-between gap-6 rounded-2xl border border-gray-200 bg-white px-6 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/3">
                <div class="flex items-center gap-2 sm:pr-3">
                    <span class="text-base font-medium text-gray-700 dark:text-gray-400">Welcome Back</span>
                    <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-lg font-medium">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </span>
                </div>

            </div>

            <div class="mt-6 grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'Total Applicants', 'value' => $metrics['total_applicants']],
                            ['label' => 'Jobs Posted', 'value' => $metrics['total_jobs']],
                            ['label' => 'Total Applications', 'value' => $metrics['total_applications']],
                            ['label' => 'Pending Applications', 'value' => $metrics['pending_applications']],
                        ] as $metric)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $formatNumber($metric['value']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-span-12">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'Approved Candidates', 'value' => $metrics['approved_candidates']],
                            ['label' => 'Rejected Candidates', 'value' => $metrics['rejected_candidates']],
                        ] as $metric)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $formatNumber($metric['value']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6 xl:col-span-7">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div>
                            <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Analytics</h3>
                            <span class="block text-theme-sm text-gray-500 dark:text-gray-400">Jobs and Applications Over This Month</span>
                        </div>
                        <div x-data="{ selected: '12_months' }"
                            class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                            <button type="button" data-analytics-period="12_months" @click="selected = '12_months'"
                                :class="selected === '12_months' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="rounded-md px-3 py-2 text-theme-sm font-medium hover:text-gray-900 dark:hover:text-white">
                                12 Months
                            </button>

                            <button type="button" data-analytics-period="30_days" @click="selected = '30_days'"
                                :class="selected === '30_days' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="rounded-md px-3 py-2 text-theme-sm font-medium hover:text-gray-900 dark:hover:text-white">
                                30 Days
                            </button>

                            <button type="button" data-analytics-period="7_days" @click="selected = '7_days'"
                                :class="selected === '7_days' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="rounded-md px-3 py-2 text-theme-sm font-medium hover:text-gray-900 dark:hover:text-white">
                                7 Days
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-4 text-theme-sm text-gray-500 dark:text-gray-400">
                        <span>Jobs: <strong id="adminAnalyticsJobsTotal" class="font-semibold text-gray-800 dark:text-white/90">0</strong></span>
                        <span>Applications: <strong id="adminAnalyticsApplicantsTotal" class="font-semibold text-gray-800 dark:text-white/90">0</strong></span>
                        <span id="adminAnalyticsEmptyState" class="hidden text-gray-400">No activity for this period.</span>
                    </div>
                    <div class="custom-scrollbar mt-4 max-w-full overflow-x-auto">
                        <div id="adminAnalyticsChart" class="-ml-5 min-w-[1300px] pl-2"></div>
                    </div>
                </section>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-5">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Application Status</h3>
                    </div>
                    <div id="adminApplicationStatusChart" class="flex justify-center mx-auto chartDarkStyle"></div>
                </section>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-5">
                    <div class="mb-5 flex justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Most Applied Jobs</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse ($mostAppliedJobs as $job)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 p-4 dark:border-gray-800">
                                <div class="min-w-0">
                                    <p class="truncate text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job['title'] }}</p>
                                    <p class="truncate text-theme-xs text-gray-500 dark:text-gray-400">{{ $job['company'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-brand-50 px-3 py-1 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $formatNumber($job['applications_count']) }}</span>
                            </div>
                        @empty
                            <div class="flex h-40 items-center justify-center rounded-lg bg-gray-50 text-theme-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No applications yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-7">
                    <div class="mb-4 px-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Applications</h3>
                    </div>
                    <div class="custom-scrollbar max-w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Applicant</p></th>
                                    <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Reference</p></th>
                                    <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Job</p></th>
                                    <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                                    <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Submitted</p></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentApplications as $application)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-6 py-3.5"><p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $application->applicant?->first_name }} {{ $application->applicant?->last_name }}</p></td>
                                        <td class="px-6 py-3.5"><p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->reference }}</p></td>
                                        <td class="px-6 py-3.5"><p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->job?->title ?? 'N/A' }}</p></td>
                                        <td class="px-6 py-3.5"><span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($application->status->label()) }}">{{ $application->status->label() }}</span></td>
                                        <td class="px-6 py-3.5"><p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->submitted_at?->diffForHumans() ?? 'N/A' }}</p></td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td colspan="5" class="px-6 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No recent applications for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        window.AdminDashboardCharts = {
            analyticsUrl: {{ Illuminate\Support\Js::from(route('employer.dashboard.analytics')) }},
            analytics: {{ Illuminate\Support\Js::from($initialAnalyticsPayload) }},
            applicationStatus: {{ Illuminate\Support\Js::from($charts['applicationStatus']) }},
        };
    </script>
    <script src="{{ asset('assets/js/admin-dashboard-charts.js') }}"></script>
</x-layout>
