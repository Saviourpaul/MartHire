<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Support\AdminDashboardDateRange;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    /**
     * @return array<string, mixed>
     */
    public function getReport(Request $request): array
    {
        $dateRange = AdminDashboardDateRange::fromRequest($request);

        return [
            'dateRange' => $dateRange,
            'filterValues' => $dateRange->filterValues(),
            'generatedAt' => now(),
            'metrics' => $this->metrics($dateRange),
            'charts' => [
                'userGrowth' => $this->userGrowth($dateRange),
                'applicationTrend' => $this->applicationTrend($dateRange),
                'jobPostingTrend' => $this->jobPostingTrend($dateRange),
                'pipeline' => $this->applicationPipeline($dateRange),
                'jobModeration' => $this->jobModeration(),
            ],
            'jobsByCategory' => $this->jobsByCategory(),
            'mostAppliedJobs' => $this->mostAppliedJobs($dateRange),
            'applicationsPerJob' => $this->applicationsPerJob($dateRange),
            'topEmployers' => $this->topEmployers($dateRange),
            'geography' => [
                'applicantsByState' => $this->usersByState(UserRole::Applicant),
                'employersByState' => $this->usersByState(UserRole::Employer),
            ],
            'operationalMetrics' => $this->operationalMetrics($dateRange),
            'securityMetrics' => $this->securityMetrics($dateRange),
            'configuration' => $this->configurationStatus(),
            'dataAvailability' => $this->dataAvailability(),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<list<string|int|float|null>>
     */
    public function csvRows(array $report): array
    {
        $rows = [
            ['Section', 'Metric', 'Value'],
            ['Report', 'Period', $report['dateRange']->label()],
            ['Report', 'Generated at', $report['generatedAt']->toDateTimeString()],
        ];

        foreach ($report['metrics'] as $key => $value) {
            $rows[] = ['Platform metrics', $this->headline($key), $value];
        }

        foreach ($report['charts']['pipeline']['series'] as $index => $value) {
            $rows[] = ['Candidate pipeline', $report['charts']['pipeline']['labels'][$index], $value];
        }

        foreach ($report['charts']['jobModeration']['series'] as $index => $value) {
            $rows[] = ['Job moderation', $report['charts']['jobModeration']['labels'][$index], $value];
        }

        foreach ($report['jobsByCategory'] as $category) {
            $rows[] = ['Jobs by category', $category['category'], $category['total_jobs']];
        }

        foreach ($report['mostAppliedJobs'] as $job) {
            $rows[] = ['Most applied jobs', $job['title'].' - '.$job['company'], $job['applications_count']];
        }

        foreach ($report['topEmployers'] as $employer) {
            $rows[] = ['Top employers', $employer['name'], $employer['applications_count']];
        }

        foreach ($report['operationalMetrics'] as $metric) {
            $rows[] = ['Operations', $metric['label'], $metric['value']];
        }

        foreach ($report['securityMetrics']['rateLimiters'] as $limiter) {
            $rows[] = ['Rate limits', $limiter['name'], $limiter['threshold']];
        }

        foreach ($report['configuration'] as $setting) {
            $rows[] = ['Configuration', $setting['label'], $setting['value']];
        }

        return $rows;
    }

    /**
     * @return array<string, int|float>
     */
    private function metrics(AdminDashboardDateRange $dateRange): array
    {
        $totalApplicants = User::query()->role(UserRole::Applicant)->count();
        $uniqueApplicantsWithApplications = ApplicationForm::query()->distinct('user_id')->count('user_id');
        $periodApplications = ApplicationForm::query()
            ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end])
            ->count();
        $periodApprovedApplications = ApplicationForm::query()
            ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end])
            ->status(ApplicationStatus::Approved)
            ->count();

        return [
            'total_registered_users' => User::query()->count(),
            'total_applicants' => $totalApplicants,
            'total_employers' => User::query()->role(UserRole::Employer)->count(),
            'active_users_in_period' => User::query()
                ->active()
                ->whereBetween('last_login_at', [$dateRange->start, $dateRange->end])
                ->count(),
            'new_users_in_period' => User::query()
                ->whereBetween('created_at', [$dateRange->start, $dateRange->end])
                ->count(),
            'active_job_postings' => Job::query()
                ->approved()
                ->whereDate('due_date', '>=', now()->toDateString())
                ->count(),
            'submitted_jobs_in_period' => Job::query()
                ->whereBetween('created_at', [$dateRange->start, $dateRange->end])
                ->count(),
            'total_applications' => ApplicationForm::query()->count(),
            'new_applications_in_period' => $periodApplications,
            'application_conversion_rate' => $this->percentage($uniqueApplicantsWithApplications, $totalApplicants),
            'hiring_success_rate' => $this->percentage($periodApprovedApplications, $periodApplications),
            'average_time_to_hire_days' => $this->averageDecisionDays($dateRange, ApplicationStatus::Approved),
            'average_time_to_decision_days' => $this->averageDecisionDays($dateRange),
            'pending_applications' => ApplicationForm::query()->status(ApplicationStatus::Pending)->count(),
            'pending_job_reviews' => Job::query()->status(JobStatus::Pending)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userGrowth(AdminDashboardDateRange $dateRange): array
    {
        $interval = $this->chartInterval($dateRange);

        return [
            'categories' => $this->bucketLabels($dateRange, $interval),
            'series' => [
                [
                    'name' => 'Applicants',
                    'data' => $this->bucketSeries(
                        User::query()
                            ->role(UserRole::Applicant)
                            ->whereBetween('created_at', [$dateRange->start, $dateRange->end]),
                        'created_at',
                        $dateRange,
                        $interval,
                    ),
                ],
                [
                    'name' => 'Employers',
                    'data' => $this->bucketSeries(
                        User::query()
                            ->role(UserRole::Employer)
                            ->whereBetween('created_at', [$dateRange->start, $dateRange->end]),
                        'created_at',
                        $dateRange,
                        $interval,
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationTrend(AdminDashboardDateRange $dateRange): array
    {
        return $this->singleSeries(
            ApplicationForm::query()->whereBetween('submitted_at', [$dateRange->start, $dateRange->end]),
            'submitted_at',
            $dateRange,
            'Applications',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPostingTrend(AdminDashboardDateRange $dateRange): array
    {
        return $this->singleSeries(
            Job::query()->whereBetween('created_at', [$dateRange->start, $dateRange->end]),
            'created_at',
            $dateRange,
            'Job Posts',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationPipeline(AdminDashboardDateRange $dateRange): array
    {
        $counts = ApplicationForm::query()
            ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_map(fn (ApplicationStatus $status): string => $status->label(), ApplicationStatus::cases()),
            'series' => array_map(fn (ApplicationStatus $status): int => (int) ($counts[$status->value] ?? 0), ApplicationStatus::cases()),
            'colors' => ['#ffc107', '#28a745', '#dc3545'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobModeration(): array
    {
        $counts = Job::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_map(fn (JobStatus $status): string => $status->label(), JobStatus::cases()),
            'series' => array_map(fn (JobStatus $status): int => (int) ($counts[$status->value] ?? 0), JobStatus::cases()),
            'colors' => ['#ffc107', '#28a745', '#dc3545'],
        ];
    }

    /**
     * @return list<array{category: string, total_jobs: int}>
     */
    private function jobsByCategory(): array
    {
        $categoryExpression = "COALESCE(NULLIF(category, ''), 'Uncategorized')";
        $normalizedJobs = Job::query()->selectRaw("{$categoryExpression} as category");

        return DB::query()
            ->fromSub($normalizedJobs, 'normalized_jobs')
            ->select('category')
            ->selectRaw('COUNT(*) as total_jobs')
            ->groupBy('category')
            ->orderByDesc('total_jobs')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'category' => $row->category,
                'total_jobs' => (int) $row->total_jobs,
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, company: string, applications_count: int}>
     */
    private function mostAppliedJobs(AdminDashboardDateRange $dateRange): array
    {
        return Job::query()
            ->select(['job_posts.id', 'job_posts.title', 'job_posts.company'])
            ->leftJoin('application_forms', function ($join) use ($dateRange): void {
                $join->on('job_posts.id', '=', 'application_forms.job_id')
                    ->whereBetween('application_forms.submitted_at', [$dateRange->start, $dateRange->end]);
            })
            ->groupBy('job_posts.id', 'job_posts.title', 'job_posts.company')
            ->selectRaw('COUNT(application_forms.id) as applications_count')
            ->havingRaw('COUNT(application_forms.id) > 0')
            ->orderByDesc('applications_count')
            ->limit(8)
            ->get()
            ->map(fn ($job): array => [
                'title' => $job->title,
                'company' => $job->company,
                'applications_count' => (int) $job->applications_count,
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, company: string, status: string, applications_count: int}>
     */
    private function applicationsPerJob(AdminDashboardDateRange $dateRange): array
    {
        return Job::query()
            ->select(['job_posts.id', 'job_posts.title', 'job_posts.company', 'job_posts.status'])
            ->leftJoin('application_forms', function ($join) use ($dateRange): void {
                $join->on('job_posts.id', '=', 'application_forms.job_id')
                    ->whereBetween('application_forms.submitted_at', [$dateRange->start, $dateRange->end]);
            })
            ->groupBy('job_posts.id', 'job_posts.title', 'job_posts.company', 'job_posts.status')
            ->selectRaw('COUNT(application_forms.id) as applications_count')
            ->orderByDesc('applications_count')
            ->orderBy('job_posts.title')
            ->limit(15)
            ->get()
            ->map(fn ($job): array => [
                'title' => $job->title,
                'company' => $job->company,
                'status' => $job->status instanceof JobStatus ? $job->status->label() : (string) $job->status,
                'applications_count' => (int) $job->applications_count,
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, jobs_count: int, applications_count: int}>
     */
    private function topEmployers(AdminDashboardDateRange $dateRange): array
    {
        return User::query()
            ->role(UserRole::Employer)
            ->select(['users.id', 'users.first_name', 'users.last_name'])
            ->leftJoin('job_posts', 'job_posts.employer_id', '=', 'users.id')
            ->leftJoin('application_forms', function ($join) use ($dateRange): void {
                $join->on('application_forms.job_id', '=', 'job_posts.id')
                    ->whereBetween('application_forms.submitted_at', [$dateRange->start, $dateRange->end]);
            })
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->selectRaw('COUNT(DISTINCT job_posts.id) as jobs_count')
            ->selectRaw('COUNT(application_forms.id) as applications_count')
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get()
            ->map(fn ($employer): array => [
                'name' => trim($employer->first_name.' '.$employer->last_name),
                'jobs_count' => (int) $employer->jobs_count,
                'applications_count' => (int) $employer->applications_count,
            ])
            ->all();
    }

    /**
     * @return list<array{state: string, total: int}>
     */
    private function usersByState(UserRole $role): array
    {
        $stateExpression = "COALESCE(NULLIF(state_of_origin, ''), 'Not Provided')";
        $normalizedUsers = User::query()
            ->role($role)
            ->selectRaw("{$stateExpression} as state");

        return DB::query()
            ->fromSub($normalizedUsers, 'normalized_users')
            ->select('state')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('state')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'state' => $row->state,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int|string}>
     */
    private function operationalMetrics(AdminDashboardDateRange $dateRange): array
    {
        return [
            [
                'label' => 'Queued jobs pending',
                'value' => DB::table('jobs')->count(),
            ],
            [
                'label' => 'Failed queued jobs',
                'value' => DB::table('failed_jobs')->count(),
            ],
            [
                'label' => 'Notifications sent in period',
                'value' => DB::table('notifications')
                    ->whereBetween('created_at', [$dateRange->start, $dateRange->end])
                    ->count(),
            ],
            [
                'label' => 'Unread notifications',
                'value' => DB::table('notifications')->whereNull('read_at')->count(),
            ],
            [
                'label' => 'Active sessions',
                'value' => DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
                    ->count(),
            ],
            [
                'label' => 'Expired approved jobs',
                'value' => Job::query()
                    ->approved()
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function securityMetrics(AdminDashboardDateRange $dateRange): array
    {
        return [
            'activeAccounts' => User::query()->status(UserStatus::Active)->count(),
            'suspendedAccounts' => User::query()->status(UserStatus::Suspended)->count(),
            'newLoginsInPeriod' => User::query()
                ->whereBetween('last_login_at', [$dateRange->start, $dateRange->end])
                ->count(),
            'rateLimiters' => [
                [
                    'name' => 'Login attempts',
                    'threshold' => '5 attempts per email/IP before lockout window',
                ],
                [
                    'name' => 'Application submissions',
                    'threshold' => '6 requests per minute per user/IP',
                ],
                [
                    'name' => 'Uploads and profile updates',
                    'threshold' => '12 requests per minute per user/IP',
                ],
                [
                    'name' => 'Document downloads/previews',
                    'threshold' => '60 requests per minute per user/IP',
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function configurationStatus(): array
    {
        return [
            ['label' => 'Environment', 'value' => app()->environment()],
            ['label' => 'Debug mode', 'value' => config('app.debug') ? 'Enabled' : 'Disabled'],
            ['label' => 'Application URL', 'value' => (string) config('app.url')],
            ['label' => 'Locale', 'value' => (string) config('app.locale')],
            ['label' => 'Cache store', 'value' => (string) config('cache.default')],
            ['label' => 'Session driver', 'value' => (string) config('session.driver')],
            ['label' => 'Session lifetime', 'value' => config('session.lifetime').' minutes'],
            ['label' => 'Queue connection', 'value' => (string) config('queue.default')],
            ['label' => 'Mail transport', 'value' => (string) config('mail.default')],
            ['label' => 'Default filesystem disk', 'value' => (string) config('filesystems.default')],
            ['label' => 'Profile image limit', 'value' => '2 MB'],
            ['label' => 'Application document limit', 'value' => '5 MB per file'],
            ['label' => 'Allowed application document types', 'value' => 'PDF, JPG, JPEG, PNG'],
        ];
    }

    /**
     * @return list<array{label: string, status: string}>
     */
    private function dataAvailability(): array
    {
        return [
            ['label' => 'Website traffic analytics', 'status' => 'Not instrumented in current schema'],
            ['label' => 'Employment type and job location fields', 'status' => 'Not tracked on job posts yet'],
            ['label' => 'Shortlist/interview/offer/hired workflow stages', 'status' => 'Current workflow supports pending, approved, rejected'],
            ['label' => 'Flagged job posts and abuse events', 'status' => 'Not instrumented in current schema'],
            ['label' => 'Rate-limit violation history', 'status' => 'Enforced in middleware/rate limiters but not persisted'],
            ['label' => 'SMS delivery status', 'status' => 'SMS gateway not configured'],
        ];
    }

    private function averageDecisionDays(AdminDashboardDateRange $dateRange, ?ApplicationStatus $status = null): float
    {
        $applications = ApplicationForm::query()
            ->whereNotNull('reviewed_at')
            ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end])
            ->when($status, fn (Builder $query): Builder => $query->status($status))
            ->get(['submitted_at', 'reviewed_at']);

        if ($applications->isEmpty()) {
            return 0.0;
        }

        $totalDays = $applications->sum(function (ApplicationForm $application): float {
            return (float) $application->submitted_at->diffInHours($application->reviewed_at) / 24;
        });

        return round($totalDays / $applications->count(), 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function singleSeries(Builder $query, string $column, AdminDashboardDateRange $dateRange, string $label): array
    {
        $interval = $this->chartInterval($dateRange);

        return [
            'categories' => $this->bucketLabels($dateRange, $interval),
            'series' => [
                [
                    'name' => $label,
                    'data' => $this->bucketSeries($query, $column, $dateRange, $interval),
                ],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function bucketSeries(Builder $query, string $column, AdminDashboardDateRange $dateRange, string $interval): array
    {
        $counts = $this->groupedCounts($query, $column, $interval);

        return array_map(
            fn (array $bucket): int => $counts[$bucket['key']] ?? 0,
            $this->periodBuckets($dateRange, $interval)
        );
    }

    /**
     * @return list<string>
     */
    private function bucketLabels(AdminDashboardDateRange $dateRange, string $interval): array
    {
        return array_map(
            fn (array $bucket): string => $bucket['label'],
            $this->periodBuckets($dateRange, $interval)
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, int>
     */
    private function groupedCounts(Builder $query, string $column, string $interval): array
    {
        $expression = $this->periodExpression($column, $interval);

        return $query
            ->selectRaw("{$expression} as period_key, COUNT(*) as total")
            ->groupBy('period_key')
            ->orderBy('period_key')
            ->pluck('total', 'period_key')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function periodBuckets(AdminDashboardDateRange $dateRange, string $interval): array
    {
        $buckets = [];

        if ($interval === 'month') {
            $period = CarbonPeriod::create(
                $dateRange->start->copy()->startOfMonth(),
                '1 month',
                $dateRange->end->copy()->startOfMonth(),
            );

            foreach ($period as $date) {
                /** @var Carbon $date */
                $buckets[] = [
                    'key' => $date->format('Y-m'),
                    'label' => $date->format('M Y'),
                ];
            }

            return $buckets;
        }

        $period = CarbonPeriod::create(
            $dateRange->start->copy()->startOfDay(),
            '1 day',
            $dateRange->end->copy()->startOfDay(),
        );

        foreach ($period as $date) {
            /** @var Carbon $date */
            $buckets[] = [
                'key' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
            ];
        }

        return $buckets;
    }

    private function chartInterval(AdminDashboardDateRange $dateRange): string
    {
        return $dateRange->start->diffInDays($dateRange->end) + 1 > 62 ? 'month' : 'day';
    }

    private function periodExpression(string $column, string $interval): string
    {
        $driver = DB::connection()->getDriverName();

        if ($interval === 'month') {
            return match ($driver) {
                'sqlite' => "strftime('%Y-%m', {$column})",
                'pgsql' => "to_char({$column}, 'YYYY-MM')",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            };
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM-DD')",
            default => "DATE({$column})",
        };
    }

    private function percentage(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    private function headline(string $value): string
    {
        return str((string) $value)->replace('_', ' ')->headline()->toString();
    }
}
