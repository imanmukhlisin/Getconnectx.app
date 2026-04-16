<?php

namespace App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingOption extends EditRecord
{
    protected static string $resource = OnboardingOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
