<?php

namespace App\Filament\Widgets;

use App\Enums\MetricType;
use App\Models\Talk;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopTalks extends BaseWidget
{
    protected static ?string $heading = 'Most popular talks';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Talk::query()
                    ->withCount([
                        'metrics as views_count' => fn (Builder $q) => $q->where('metric_type', MetricType::View)->where('is_bot', false),
                        'metrics as downloads_count' => fn (Builder $q) => $q->where('metric_type', MetricType::Download)->where('is_bot', false),
                    ])
                    ->orderByDesc('views_count')
            )
            ->paginationPageOptions([10, 25])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('title')
                    ->limit(70)
                    ->wrap()
                    ->url(fn (Talk $record) => route('talks.show', $record), true),
                TextColumn::make('views_count')
                    ->label('Views')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('downloads_count')
                    ->label('Downloads')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ]);
    }
}
