<?php

namespace App\Filament\Resources\StartupResource\Pages;

use App\Filament\Resources\StartupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStartup extends EditRecord
{
    protected static string $resource = StartupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
