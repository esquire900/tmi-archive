<?php

namespace App\Filament\Widgets;

use App\Enums\MetricType;
use App\Models\Talk;
use App\Models\TalkMetric;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MetricsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Overview';

    protected function getStats(): array
    {
        $since = now()->subDays(30);

        $views = TalkMetric::query()->where('metric_type', MetricType::View)->where('is_bot', false);
        $downloads = TalkMetric::query()->where('metric_type', MetricType::Download)->where('is_bot', false);

        $views30 = (clone $views)->where('created_at', '>=', $since)->count();
        $downloads30 = (clone $downloads)->where('created_at', '>=', $since)->count();

        $uniqueVisitors30 = TalkMetric::query()
            ->where('is_bot', false)
            ->where('created_at', '>=', $since)
            ->distinct()
            ->count('ip');

        $botShare = $this->botSharePercent($since);

        return [
            Stat::make('Talks', number_format(Talk::count()))
                ->description('Total talks in the archive')
                ->color('primary'),

            Stat::make('Views (30d)', number_format($views30))
                ->description(number_format($views->count()).' all-time')
                ->descriptionIcon('heroicon-m-eye')
                ->color('success'),

            Stat::make('Downloads (30d)', number_format($downloads30))
                ->description(number_format($downloads->count()).' all-time')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),

            Stat::make('Unique visitors (30d)', number_format($uniqueVisitors30))
                ->description($botShare.'% of raw hits filtered as bots')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }

    protected function botSharePercent(\Carbon\CarbonInterface $since): float
    {
        $total = TalkMetric::query()->where('created_at', '>=', $since)->count();
        if ($total === 0) {
            return 0.0;
        }
        $bots = TalkMetric::query()->where('created_at', '>=', $since)->where('is_bot', true)->count();

        return round($bots / $total * 100, 1);
    }
}
