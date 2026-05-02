<?php

namespace App\Filament\Resources\NotificationTemplateResource\Pages;

use App\Filament\Resources\NotificationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['subject_id'] = $data['title']['id'] ?? '';
        $data['subject_en'] = $data['title']['en'] ?? '';
        $data['body_id'] = $data['body']['id'] ?? '';
        $data['body_en'] = $data['body']['en'] ?? '';
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['title'] = [
            'id' => $data['subject_id'],
            'en' => $data['subject_en'],
        ];
        
        $data['body'] = [
            'id' => $data['body_id'],
            'en' => $data['body_en'],
        ];
        
        unset($data['subject_id'], $data['subject_en'], $data['body_id'], $data['body_en']);
        
        return $data;
    }
}
