<?php

namespace App\Filament\Resources\Onboarding\OnboardingStepResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingStep extends EditRecord
{
    protected static string $resource = OnboardingStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
