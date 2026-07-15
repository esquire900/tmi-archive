<?php

namespace App\Filament\Resources\Talks\Tables;

use App\Enums\MetricType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TalksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->wrap(),
                IconColumn::make('has_audio')
                    ->label('Audio')
                    ->boolean()
                    ->state(fn ($record) => $record->has_audio),
                TextColumn::make('recorded_date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('views_count')
                    ->label('Views')
                    ->counts(['metrics as views_count' => fn (Builder $q) => $q->where('metric_type', MetricType::View)->where('is_bot', false)])
                    ->sortable(),
                TextColumn::make('downloads_count')
                    ->label('Downloads')
                    ->counts(['metrics as downloads_count' => fn (Builder $q) => $q->where('metric_type', MetricType::Download)->where('is_bot', false)])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_audio')
                    ->label('Has audio')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('audio_original')->where('audio_original', '!=', ''),
                        false: fn (Builder $q) => $q->where(fn (Builder $sub) => $sub->whereNull('audio_original')->orWhere('audio_original', '')),
                        blank: fn (Builder $q) => $q,
                    ),
                Filter::make('has_transcription')
                    ->label('Has transcription')
                    ->query(fn (Builder $q) => $q->whereNotNull('transcription')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
