<?php

namespace App\Support;

use App\Enums\DashboardPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardDateRange
{
    public function __construct(
        public readonly DashboardPeriod $period,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = DashboardPeriod::tryFromRequest($request->input('period'));
        $now = now();

        return match ($period) {
            DashboardPeriod::Today => new self(
                period: $period,
                start: $now->copy()->startOfDay(),
                end: $now->copy()->endOfDay(),
            ),
            DashboardPeriod::ThisWeek => new self(
                period: $period,
                start: $now->copy()->startOfWeek(),
                end: $now->copy()->endOfWeek(),
            ),
            DashboardPeriod::ThisMonth => new self(
                period: $period,
                start: $now->copy()->startOfMonth(),
                end: $now->copy()->endOfMonth(),
            ),
            DashboardPeriod::ThisYear => new self(
                period: $period,
                start: $now->copy()->startOfYear(),
                end: $now->copy()->endOfYear(),
            ),
            DashboardPeriod::Custom => self::fromCustomRange(
                $request->input('date_from'),
                $request->input('date_to'),
            ),
        };
    }

    public function label(): string
    {
        if ($this->period === DashboardPeriod::Custom) {
            return sprintf(
                '%s - %s',
                $this->start->format('M j, Y'),
                $this->end->format('M j, Y'),
            );
        }

        return $this->period->label();
    }

    /**
     * @return array<string, string|null>
     */
    public function filterValues(): array
    {
        return [
            'period' => $this->period->value,
            'date_from' => $this->dateFrom ?? $this->start->toDateString(),
            'date_to' => $this->dateTo ?? $this->end->toDateString(),
        ];
    }

    private static function fromCustomRange(?string $dateFrom, ?string $dateTo): self
    {
        $start = filled($dateFrom)
            ? Carbon::parse($dateFrom)->startOfDay()
            : now()->copy()->startOfMonth()->startOfDay();

        $end = filled($dateTo)
            ? Carbon::parse($dateTo)->endOfDay()
            : now()->copy()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return new self(
            period: DashboardPeriod::Custom,
            start: $start,
            end: $end,
            dateFrom: $start->toDateString(),
            dateTo: $end->toDateString(),
        );
    }
}
