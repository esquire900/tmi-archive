<?php

namespace App\Filament\Resources\Talks\Pages;

use App\Filament\Resources\Talks\TalkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTalk extends EditRecord
{
    protected static string $resource = TalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
