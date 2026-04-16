<?php

namespace App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingQuestion extends EditRecord
{
    protected static string $resource = OnboardingQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
