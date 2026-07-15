<?php

namespace App\Filament\Resources\Talks\Pages;

use App\Filament\Resources\Talks\TalkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTalks extends ListRecords
{
    protected static string $resource = TalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
