<?php

namespace App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingOptions extends ListRecords
{
    protected static string $resource = OnboardingOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
