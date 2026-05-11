<?php

namespace App\Filament\Resources\WhatsappBlastResource\Pages;

use App\Filament\Resources\WhatsappBlastResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappBlast extends EditRecord
{
    protected static string $resource = WhatsappBlastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
