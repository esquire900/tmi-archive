<?php

namespace App\Filament\Resources\Talks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TalkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Talk')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(300)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->helperText('A short description of the talk (not the transcription itself).')
                            ->columnSpanFull(),
                        DatePicker::make('recorded_date')
                            ->helperText('Date the talk was recorded, if known.'),
                        TextInput::make('audio_length')
                            ->label('Audio length (seconds)')
                            ->numeric(),
                    ]),

                Section::make('Audio files')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('audio_original')
                            ->label('Original audio path')
                            ->helperText('Relative path on the media host, e.g. "8/original.mp3".')
                            ->maxLength(255),
                        TextInput::make('audio_cleaned')
                            ->label('Cleaned audio path')
                            ->maxLength(255),
                        TextInput::make('original_file_name')
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ]),

                Section::make('Transcription')
                    ->collapsed()
                    ->schema([
                        Textarea::make('transcription')
                            ->rows(10)
                            ->columnSpanFull(),
                        Textarea::make('whisper_transcription')
                            ->label('Whisper transcription (JSON)')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
