<?php

namespace App\Filament\Resources\Onboarding\OnboardingFlowResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingFlow extends EditRecord
{
    protected static string $resource = OnboardingFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
