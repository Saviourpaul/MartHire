<x-layout>
    @php

        $metrics = $report['metrics'];
        $charts = $report['charts'];
        $dateRange = $report['dateRange'];
        $filters = $report['filterValues'];

        $formatValue = function (int|float|string|null $value, string $suffix = ''): string {
            if (is_numeric($value)) {
                $number = (float) $value;
                $formatted = floor($number) !== $number ? number_format($number, 1) : number_format((int) $number);

                return $formatted.$suffix;
            }

            return filled($value) ? (string) $value : 'Not available';
        };

        $linePoints = function (array $values, int $width = 720, int $height = 220): string {
            $max = max(max($values ?: [0]), 1);
            $count = count($values);

            return collect($values)->map(function ($value, $index) use ($width, $height, $max, $count) {
                $x = $count <= 1 ? $width / 2 : ($width / ($count - 1)) * $index;
                $y = $height - (((int) $value / $max) * ($height - 24)) - 12;

                return round($x, 2).','.round($y, 2);
            })->implode(' ');
        };

        $totalSeries = fn (array $chart): int => collect($chart['series'] ?? [])->sum(fn ($series) => array_sum($series['data'] ?? []));
        $statusClass = function (string $status): string {
            return match (strtolower($status)) {
                'approved', 'active' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                'rejected', 'suspended' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            };
        };
    @endphp

    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Reports</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        {{ $dateRange->label() }} report generated {{ $report['generatedAt']->format('M j, Y g:i A') }}.
                    </p>
                </div>

                <form method="GET" action="{{ route('Reports') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="period" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Period</label>
                        <select id="period" name="period" class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach (\App\Enums\DashboardPeriod::cases() as $period)
                                <option value="{{ $period->value }}" @selected($filters['period'] === $period->value)>{{ $period->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>

                    <div>
                        <label for="date_to" class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>

                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Apply</button>
                    <a href="{{ route('Reports.export', request()->query()) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Export CSV</a>
                </form>
            </div>

            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'Registered Users', 'value' => $metrics['total_registered_users']],
                            ['label' => 'Applicants', 'value' => $metrics['total_applicants']],
                            ['label' => 'Employers', 'value' => $metrics['total_employers']],
                            ['label' => 'Active Users In Period', 'value' => $metrics['active_users_in_period']],
                        ] as $metric)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $formatValue($metric['value']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-span-12">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'New Users In Period', 'value' => $metrics['new_users_in_period']],
                            ['label' => 'Active Job Postings', 'value' => $metrics['active_job_postings']],
                            ['label' => 'Applications In Period', 'value' => $metrics['new_applications_in_period']],
                            ['label' => 'Pending Reviews', 'value' => $metrics['pending_applications'] + $metrics['pending_job_reviews']],
                        ] as $metric)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $formatValue($metric['value']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-span-12">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'Application Conversion', 'value' => $metrics['application_conversion_rate'], 'suffix' => '%'],
                            ['label' => 'Hiring Success Rate', 'value' => $metrics['hiring_success_rate'], 'suffix' => '%'],
                            ['label' => 'Avg. Time To Hire', 'value' => $metrics['average_time_to_hire_days'], 'suffix' => ' days'],
                            ['label' => 'Avg. Time To Decision', 'value' => $metrics['average_time_to_decision_days'], 'suffix' => ' days'],
                        ] as $metric)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $formatValue($metric['value'], $metric['suffix']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Growth Trends</h2>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Users, applications, and jobs over the selected period.</p>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-3">
                        @foreach ([
                            ['title' => 'User Growth', 'chart' => $charts['userGrowth'], 'color' => '#465fff'],
                            ['title' => 'Applications', 'chart' => $charts['applicationTrend'], 'color' => '#12b76a'],
                            ['title' => 'Job Posts', 'chart' => $charts['jobPostingTrend'], 'color' => '#f79009'],
                        ] as $item)
                            <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $item['title'] }}</h3>
                                    <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ number_format($totalSeries($item['chart'])) }} total</span>
                                </div>

                                @if ($totalSeries($item['chart']) > 0)
                                    <div class="max-w-full overflow-x-auto">
                                        <svg viewBox="0 0 720 260" role="img" aria-label="{{ $item['title'] }} trend chart" class="h-56 min-w-[640px] w-full">
                                            @for ($line = 0; $line <= 4; $line++)
                                                <line x1="0" x2="720" y1="{{ 20 + ($line * 50) }}" y2="{{ 20 + ($line * 50) }}" stroke="currentColor" class="text-gray-100 dark:text-gray-800" />
                                            @endfor
                                            @foreach ($item['chart']['series'] as $series)
                                                <polyline points="{{ $linePoints($series['data']) }}" fill="none" stroke="{{ $loop->first ? $item['color'] : '#9b8afb' }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                                            @endforeach
                                            <text x="0" y="252" class="fill-gray-400 text-[12px]">{{ $item['chart']['categories'][0] ?? '' }}</text>
                                            <text x="720" y="252" text-anchor="end" class="fill-gray-400 text-[12px]">{{ $item['chart']['categories'][count($item['chart']['categories']) - 1] ?? '' }}</text>
                                        </svg>
                                    </div>
                                @else
                                    <div class="flex h-56 items-center justify-center rounded-lg bg-gray-50 text-theme-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No data available for this period.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h2 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">Candidate Pipeline</h2>
                    @include('admin.partials.report-distribution', ['chart' => $charts['pipeline'], 'statusClass' => $statusClass])
                </section>

                <section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h2 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">Job Moderation</h2>
                    @include('admin.partials.report-distribution', ['chart' => $charts['jobModeration'], 'statusClass' => $statusClass])
                </section>

                @include('admin.partials.report-table', ['title' => 'Most Applied Jobs', 'description' => 'Jobs with the most applications in the selected period.', 'headers' => ['Job', 'Company', 'Applications'], 'rows' => collect($report['mostAppliedJobs'])->map(fn ($job) => [$job['title'], $job['company'], number_format($job['applications_count'])])->all()])
                @include('admin.partials.report-table', ['title' => 'Top Employers', 'description' => 'Employers ranked by application volume in the selected period.', 'headers' => ['Employer', 'Jobs', 'Applications'], 'rows' => collect($report['topEmployers'])->map(fn ($employer) => [$employer['name'] ?: 'Unnamed employer', number_format($employer['jobs_count']), number_format($employer['applications_count'])])->all()])
                @include('admin.partials.report-table', ['title' => 'Jobs By Category', 'description' => 'Top job categories by posting volume.', 'headers' => ['Category', 'Jobs'], 'rows' => collect($report['jobsByCategory'])->map(fn ($category) => [$category['category'], number_format($category['total_jobs'])])->all()])
                @include('admin.partials.report-table', ['title' => 'Employers By State', 'description' => 'Top employer locations from profile data.', 'headers' => ['State', 'Employers'], 'rows' => collect($report['geography']['employersByState'])->map(fn ($state) => [$state['state'], number_format($state['total'])])->all()])
               </div>
        </div>
    </main>
</x-layout>
