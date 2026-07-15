<?php

namespace App\Filament\Resources\Playlists\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlaylistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->columnSpanFull(),
                DatePicker::make('first_recording_date')
                    ->helperText('Approximate date of the first recording.'),
                Select::make('talks')
                    ->relationship('talks', 'title')
                    ->multiple()
                    ->searchable()
                    ->preload(false)
                    ->helperText('Talks included in this playlist. Order follows selection order.')
                    ->columnSpanFull(),
            ]);
    }
}
