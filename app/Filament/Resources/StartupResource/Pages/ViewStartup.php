<?php

namespace App\Filament\Resources\StartupResource\Pages;

use App\Filament\Resources\StartupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStartup extends ViewRecord
{
    protected static string $resource = StartupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
