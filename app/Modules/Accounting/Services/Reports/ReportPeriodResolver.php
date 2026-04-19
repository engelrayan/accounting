<?php

namespace App\Modules\Accounting\Services\Reports;

use Carbon\Carbon;

class ReportPeriodResolver
{
    /**
     * Resolve preset/custom period filters into a normalized range.
     *
     * @return array{
     *   period:string|null,
     *   from:string|null,
     *   to:string|null,
     *   label:string,
     *   human_range:string,
     *   days_count:int|null,
     * }
     */
    public function resolve(
        ?string $period,
        ?string $from,
        ?string $to,
        ?string $defaultPreset = null,
    ): array {
        $period = in_array($period, ['week', 'month', 'custom'], true)
            ? $period
            : null;

        if (! $period && ($from || $to)) {
            $period = 'custom';
        }

        if (! $period && $defaultPreset) {
            $period = $defaultPreset;
        }

        if (! $period) {
            return [
                'period'      => null,
                'from'        => null,
                'to'          => null,
                'label'       => 'كل الفترات',
                'human_range' => 'منذ بداية البيانات وحتى اليوم',
                'days_count'  => null,
            ];
        }

        [$start, $end] = match ($period) {
            'week' => [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay(),
            ],
            'month' => [
                today()->startOfMonth()->startOfDay(),
                today()->endOfDay(),
            ],
            'custom' => $this->resolveCustomRange($from, $to),
        };

        return [
            'period'      => $period,
            'from'        => $start->toDateString(),
            'to'          => $end->toDateString(),
            'label'       => $this->labelFor($period),
            'human_range' => $this->humanRange($start, $end),
            'days_count'  => $start->diffInDays($end) + 1,
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveCustomRange(?string $from, ?string $to): array
    {
        $end = $to
            ? Carbon::parse($to)->endOfDay()
            : today()->endOfDay();

        $start = $from
            ? Carbon::parse($from)->startOfDay()
            : $end->copy()->startOfMonth()->startOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function labelFor(string $period): string
    {
        return match ($period) {
            'week'   => 'آخر 7 أيام',
            'month'  => 'هذا الشهر',
            'custom' => 'فترة مخصصة',
        };
    }

    private function humanRange(Carbon $start, Carbon $end): string
    {
        $format = fn (Carbon $date) => $date->locale('ar')->translatedFormat('j F Y');

        return $format($start) . ' - ' . $format($end);
    }
}
