<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Support\AdminDashboardDateRange;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployerDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getOverview(Request $request, User $employer): array
    {
        $analytics = $this->analytics($employer, $request->input('period', '12_months'));
        $dateRange = $this->analyticsDateRange($request->input('period', '12_months'));

        return [
            'dateRange' => $dateRange,
            'filterValues' => ['period' => $request->input('period', '12_months')],
            'metrics' => $this->metrics($employer),
            'charts' => [
                'applicationsOverTime' => [
                    'label' => $analytics['series'][1]['name'],
                    'categories' => $analytics['categories'],
                    'series' => $analytics['series'][1]['data'],
                ],
                'jobsOverTime' => [
                    'label' => $analytics['series'][0]['name'],
                    'categories' => $analytics['categories'],
                    'series' => $analytics['series'][0]['data'],
                ],
                'applicationStatus' => $analytics['applicationStatus'],
            ],
            'mostAppliedJobs' => $this->mostAppliedJobs($employer),
            'recentApplications' => $this->recentApplications($employer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analytics(User $employer, string $period = '12_months'): array
    {
        $dateRange = $this->analyticsDateRange($period);
        $jobs = $this->jobsOverTime($employer, $dateRange);
        $applications = $this->applicationsOverTime($employer, $dateRange);

        return [
            'period' => $period,
            'label' => $dateRange->label(),
            'categories' => $jobs['categories'],
            'series' => [
                [
                    'name' => $jobs['label'],
                    'data' => $jobs['series'],
                ],
                [
                    'name' => $applications['label'],
                    'data' => $applications['series'],
                ],
            ],
            'totals' => [
                'jobs' => array_sum($jobs['series']),
                'applicants' => array_sum($applications['series']),
            ],
            'empty' => array_sum($jobs['series']) + array_sum($applications['series']) === 0,
            'applicationStatus' => $this->applicationStatusDistribution($employer, $dateRange),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function metrics(User $employer): array
    {
        $applicationsQuery = ApplicationForm::query()->forEmployer($employer);

        $applicationCounts = $applicationsQuery
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_jobs' => Job::query()->where('employer_id', $employer->id)->count(),
            'total_applicants' => ApplicationForm::query()->forEmployer($employer)->distinct('user_id')->count('user_id'),
            'total_applications' => (int) $applicationCounts->sum(),
            'approved_candidates' => (int) ($applicationCounts[ApplicationStatus::Approved->value] ?? 0),
            'rejected_candidates' => (int) ($applicationCounts[ApplicationStatus::Rejected->value] ?? 0),
            'pending_applications' => (int) ($applicationCounts[ApplicationStatus::Pending->value] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationsOverTime(User $employer, AdminDashboardDateRange $dateRange): array
    {
        $interval = $this->chartInterval($dateRange);
        $counts = $this->groupedCounts(
            ApplicationForm::query()
                ->forEmployer($employer)
                ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end]),
            'submitted_at',
            $interval,
        );

        return $this->buildTimeSeries($dateRange, $interval, $counts, 'Applications Received');
    }

    /**
     * @return array<string, mixed>
     */
    private function jobsOverTime(User $employer, AdminDashboardDateRange $dateRange): array
    {
        $interval = $this->chartInterval($dateRange);
        $counts = $this->groupedCounts(
            Job::query()
                ->where('employer_id', $employer->id)
                ->whereBetween('created_at', [$dateRange->start, $dateRange->end]),
            'created_at',
            $interval,
        );

        return $this->buildTimeSeries($dateRange, $interval, $counts, 'Jobs Posted');
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationStatusDistribution(User $employer, AdminDashboardDateRange $dateRange): array
    {
        $counts = ApplicationForm::query()
            ->forEmployer($employer)
            ->whereBetween('submitted_at', [$dateRange->start, $dateRange->end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $series = [];
        $colors = [];

        foreach ([ApplicationStatus::Approved, ApplicationStatus::Pending, ApplicationStatus::Rejected] as $status) {
            $labels[] = $status->label();
            $series[] = (int) ($counts[$status->value] ?? 0);
            $colors[] = match ($status) {
                ApplicationStatus::Approved => '#3641f5',
                ApplicationStatus::Pending => '#7592ff',
                ApplicationStatus::Rejected => '#dde9ff',
            };
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => $colors,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mostAppliedJobs(User $employer): array
    {
        return ApplicationForm::query()
            ->forEmployer($employer)
            ->select('application_forms.job_id', 'job_posts.title', 'job_posts.company')
            ->join('job_posts', 'application_forms.job_id', '=', 'job_posts.id')
            ->groupBy('application_forms.job_id', 'job_posts.title', 'job_posts.company')
            ->selectRaw('COUNT(*) as applications_count')
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'title' => $row->title,
                'company' => $row->company,
                'applications_count' => (int) $row->applications_count,
            ])
            ->all();
    }

    /**
     * @return Collection<int, ApplicationForm>
     */
    private function recentApplications(User $employer): Collection
    {
        return ApplicationForm::query()
            ->forEmployer($employer)
            ->with(['job:id,title', 'applicant:id,first_name,last_name'])
            ->latest('submitted_at')
            ->limit(5)
            ->get(['id', 'reference', 'job_id', 'user_id', 'status', 'submitted_at']);
    }

    private function analyticsDateRange(?string $period): AdminDashboardDateRange
    {
        $now = now();

        return match ($period) {
            '30_days' => new AdminDashboardDateRange(
                period: \App\Enums\DashboardPeriod::Custom,
                start: $now->copy()->subDays(29)->startOfDay(),
                end: $now->copy()->endOfDay(),
            ),
            '7_days' => new AdminDashboardDateRange(
                period: \App\Enums\DashboardPeriod::Custom,
                start: $now->copy()->subDays(6)->startOfDay(),
                end: $now->copy()->endOfDay(),
            ),
            default => new AdminDashboardDateRange(
                period: \App\Enums\DashboardPeriod::Custom,
                start: $now->copy()->subMonths(11)->startOfMonth(),
                end: $now->copy()->endOfDay(),
            ),
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, int>
     */
    private function groupedCounts(Builder $query, string $column, string $interval): array
    {
        $query = clone $query;
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
     * @param  array<string, int>  $counts
     * @return array<string, mixed>
     */
    private function buildTimeSeries(
        AdminDashboardDateRange $dateRange,
        string $interval,
        array $counts,
        string $label,
    ): array {
        $categories = [];
        $series = [];

        foreach ($this->periodBuckets($dateRange, $interval) as $bucket) {
            $categories[] = $bucket['label'];
            $series[] = $counts[$bucket['key']] ?? 0;
        }

        return [
            'label' => $label,
            'categories' => $categories,
            'series' => $series,
        ];
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
        $days = $dateRange->start->diffInDays($dateRange->end) + 1;

        return $days > 62 ? 'month' : 'day';
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
}
