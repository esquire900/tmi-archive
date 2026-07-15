<?php

namespace App\Filament\Widgets;

use App\Enums\MetricType;
use App\Models\TalkMetric;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MetricsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Views & downloads by month';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // Anchor to the most recent metric so the chart is meaningful even for
        // an archive whose traffic is historical.
        $latest = TalkMetric::max('created_at');
        $end = $latest ? CarbonImmutable::parse($latest)->endOfMonth() : CarbonImmutable::now()->endOfMonth();
        $start = $end->subMonths(11)->startOfMonth();

        $rows = TalkMetric::query()
            ->where('is_bot', false)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, metric_type, COUNT(*) as c")
            ->groupBy('ym', 'metric_type')
            ->pluck('c', DB::raw("CONCAT(ym, '-', metric_type)"))
            ->all();

        $labels = [];
        $views = [];
        $downloads = [];

        for ($m = $start; $m <= $end; $m = $m->addMonth()) {
            $ym = $m->format('Y-m');
            $labels[] = $m->format('M Y');
            $views[] = (int) ($rows[$ym.'-'.MetricType::View->value] ?? 0);
            $downloads[] = (int) ($rows[$ym.'-'.MetricType::Download->value] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $views,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.15)',
                    'fill' => true,
                ],
                [
                    'label' => 'Downloads',
                    'data' => $downloads,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37,99,235,0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
