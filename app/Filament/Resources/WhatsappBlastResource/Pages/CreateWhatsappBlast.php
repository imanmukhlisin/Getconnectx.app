<?php

namespace App\Filament\Resources\WhatsappBlastResource\Pages;

use App\Filament\Resources\WhatsappBlastResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappBlast extends CreateRecord
{
    protected static string $resource = WhatsappBlastResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Campaign blast berhasil dibuat!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Normalize custom phone numbers from textarea (one per line) to array
        if ($data['target_segment'] === 'custom' && !empty($data['recipient_phones'])) {
            if (is_string($data['recipient_phones'])) {
                $phones = array_filter(
                    array_map('trim', explode("\n", $data['recipient_phones']))
                );
                $data['recipient_phones'] = array_values($phones);
                $data['total_recipients'] = count($phones);
            }
        } elseif ($data['target_segment'] !== 'custom') {
            $data['recipient_phones'] = null;
        }

        return $data;
    }
}
