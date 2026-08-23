<x-layout>
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
			<div class="flex flex-col justify-between gap-6 rounded-2xl border border-gray-200 bg-white px-6 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/3">
                  <div class="flex items-center gap-2 sm:pr-3">
                    <span class="text-base font-medium text-gray-700 dark:text-gray-400">
                      Welcome Back</span>
                    <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-lg font-medium">{{ auth()->user()->first_name  }} {{ auth()->user()->last_name }}</span>
                  </div>
              </div>
			  <br>
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <!-- Metric Group Two -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                Total Applicants
                            </p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                        {{ number_format($metrics['total_applicants']) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <!-- Metric Item End -->

                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                Total Employers
                            </p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                        {{ number_format($metrics['total_employers'])   }} 
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <!-- Metric Item End -->

                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Jobs Posted</p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($metrics['total_jobs']) }}</h4>
								</div>
                            </div>
                        </div>
                        <!-- Metric Item End -->

                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Applications</p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                        {{ number_format($metrics['total_applications']) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <!-- Metric Item End -->
                    </div>
                    <!-- Metric Group Two -->
                </div>
                <div class="col-span-12">
                    <!-- Metric Group Two -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                Approved Candidates
                            </p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                        {{ number_format($metrics['approved_candidates']) }}
                                    </h4>
                                </div>
                               
                            </div>
                        </div>
                        <!-- Metric Item End -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                Pending Applications
                            </p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                        {{ number_format($metrics['pending_applications']) }}
                                    </h4>
                                </div>
                               
                            </div>
                        </div>

                        <!-- Metric Item Start -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                Rejected Candidates
                            </p>

                            <div class="mt-3 flex items-end justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                                       {{ number_format($metrics['rejected_candidates']) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <!-- Metric Item End -->
                    </div>
                    <!-- Metric Group Two -->
                </div>

                <div class="col-span-12">
                    <!-- ====== Chart Four Start -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                        <div class="flex flex-wrap items-start justify-between gap-5">
                            <div>
                                <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                                    Analytics
                                </h3>
                                <span class="block text-theme-sm text-gray-500 dark:text-gray-400">
                                    Jobs and Applicants Over Time
                                </span>
                            </div>

                            <div x-data="{ selected: '12_months' }"
                                class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                                <button type="button" data-analytics-period="12_months" @click="selected = '12_months'"
                                    :class="selected === '12_months' ?
                                        'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                        'text-gray-500 dark:text-gray-400'"
                                    class=":hover:text-white rounded-md px-3 py-2 text-theme-sm font-medium hover:text-gray-900">
                                    12 Months
                                </button>

                                <button type="button" data-analytics-period="30_days" @click="selected = '30_days'"
                                    :class="selected === '30_days' ?
                                        'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                        'text-gray-500 dark:text-gray-400'"
                                    class="hover:text-gray-900dark:hover:text-white rounded-md px-3 py-2 text-theme-sm font-medium">
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
                            <span>Applicants: <strong id="adminAnalyticsApplicantsTotal" class="font-semibold text-gray-800 dark:text-white/90">0</strong></span>
                            <span id="adminAnalyticsEmptyState" class="hidden text-gray-400">No activity for this period.</span>
                        </div>
                        <div class="custom-scrollbar max-w-full overflow-x-auto">
                            <div id="adminAnalyticsChart" class="-ml-5 min-w-[1300px] pl-2"></div>
                        </div>
                    </div>
                    <!-- ====== Chart Four End -->
                </div>

                

                <div class="col-span-12 xl:col-span-5">
                    <!-- ====== Chart Five Start -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Active Users
                            </h3>

                           
                        </div>

                        <div class="mt-6 flex items-end gap-1.5">
                            <div class="flex items-center gap-2.5">
                                <span class="relative inline-block w-5 h-5">
                                    <span
                                        class="absolute w-2 h-2 transform -translate-x-1/2 -translate-y-1/2 rounded-full bg-error-500 top-1/2 left-1/2">
                                        <span
                                            class="absolute inline-flex w-4 h-4 rounded-full opacity-75 bg-error-400 -top-1 -left-1 animate-ping">
                                        </span> </span></span>
                                <span class="font-semibold text-gray-800 activeUsers text-title-sm dark:text-white/90">
                                    {{ number_format($liveVisitors) }}
                                </span>
                            </div>
                            <span class="block mb-1 text-gray-500 text-theme-sm dark:text-gray-400">
                                Live visitors
                            </span>
                        </div>

                        <div class="my-5 min-h-[155px] rounded-xl bg-gray-50 dark:bg-gray-900">
                            <div id="adminActiveUsersChart" class="-mr-2.5 -ml-[22px] h-full"></div>
                        </div>

                        
                    </div>
                    <!-- ====== Chart Five End -->
                </div>

              

                <div class="col-span-12 xl:col-span-5">
                    <!-- ====== Chart Seven Start -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between mb-9">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Application Status
                            </h3>
                        </div>
                        <div class="">
                            <div id="adminApplicationStatusChart" class="flex justify-center mx-auto chartDarkStyle"></div>
                        </div>
                    </div>
                    <!-- ====== Chart Seven End -->
                </div>

                <div class="col-span-12 xl:col-span-5">
					
                    <!-- ====== Map One Start -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
						
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                    Latest Applications
                                </h3>
                               
                            </div>

                           
                        </div>
						 
                       <div class="max-w-full overflow-x-auto custom-scrollbar">
						
                            <table class="w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
										<th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Applicants
                                            </p>
                                        </th>
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Applications
                                            </p>
                                        </th>
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                job 
                                            </p>
                                        </th>
                                        
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                status
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
									@forelse ($recentActivities['applications'] as $application)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-6 py-3.5">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                               {{ $application->applicant?->first_name }} {{ $application->applicant?->last_name }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $application->reference }}</p>
                                        </td>
                                        
                                        <td class="px-6 py-3.5">
                                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                               {{ $application->job?->title ?? '—' }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <p class="text-theme-sm text-success-600">{{ $application->status->label() }}</p>
                                        </td>
                                    </tr>
									@empty
									<tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-6 py-3.5">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                                No Applications
                                            </p>
                                        </td>
                                        
                                    </tr>
									@endforelse
                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- ====== Map One End -->
                </div>

                <div class="col-span-12 xl:col-span-7">
                    <!-- ====== Table Two Start -->
                    <div
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                    Recent Registrations
                                </h3>
                            </div>
                        </div>

                        <div class="max-w-full overflow-x-auto custom-scrollbar">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Users
                                            </p>
                                        </th>
										 <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                email
                                            </p>
                                        </th>
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                 Registered
                                            </p>
                                        </th>
                                        <th class="px-6 py-3 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                               Role
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                 <tbody>
									@forelse ($recentActivities['users'] as $user)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-6 py-3.5">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                               {{ $user->first_name }} {{ $user->last_name }}
                                            </p>
                                        </td>
										<td class="px-6 py-3.5">
                                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                               {{ $user->email }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                               {{ $user->created_at->diffForHumans() }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <p class="text-theme-sm text-success-600">{{ $user->role->label() }}</p>
                                        </td>
                                    </tr>
									@empty
									<tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td colspan="4" class="px-6 py-3.5">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                                No Recent Registrations.
                                            </p>
                                        </td>
                                    </tr>
								@endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- ====== Table Two End -->
                </div>
            </div>
        </div>
    </main>
    @php
        $initialAnalyticsPayload = [
            'categories' => $charts['jobsOverTime']['categories'],
            'series' => [
                ['name' => $charts['jobsOverTime']['label'], 'data' => $charts['jobsOverTime']['series']],
                ['name' => $charts['applicantsOverTime']['label'], 'data' => $charts['applicantsOverTime']['series']],
            ],
            'totals' => [
                'jobs' => array_sum($charts['jobsOverTime']['series']),
                'applicants' => array_sum($charts['applicantsOverTime']['series']),
            ],
        ];
        $initialAnalyticsPayload['empty'] = ($initialAnalyticsPayload['totals']['jobs'] + $initialAnalyticsPayload['totals']['applicants']) === 0;
        $initialAnalyticsPayload['applicationStatus'] = $charts['applicationStatus'];
    @endphp
    <script>
        window.AdminDashboardCharts = {
            analyticsUrl: {{ Illuminate\Support\Js::from(route('admin.dashboard.analytics')) }},
            activeUsersUrl: {{ Illuminate\Support\Js::from(route('admin.dashboard.active-users')) }},
            analytics: {{ Illuminate\Support\Js::from($initialAnalyticsPayload) }},
            applicationStatus: {{ Illuminate\Support\Js::from($charts['applicationStatus']) }},
            activeUsersTrend: {{ Illuminate\Support\Js::from($activeUsersTrend) }},
        };
    </script>
    <script src="{{ asset('assets/js/admin-dashboard-charts.js') }}"></script>
</x-layout>
