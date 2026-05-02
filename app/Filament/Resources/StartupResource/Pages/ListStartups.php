<?php

namespace App\Filament\Resources\StartupResource\Pages;

use App\Filament\Resources\StartupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStartups extends ListRecords
{
    protected static string $resource = StartupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
