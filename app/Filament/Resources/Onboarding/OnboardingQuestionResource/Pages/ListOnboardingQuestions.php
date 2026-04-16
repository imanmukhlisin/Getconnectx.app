<?php

namespace App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages;

use App\Filament\Resources\Onboarding\OnboardingQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingQuestions extends ListRecords
{
    protected static string $resource = OnboardingQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
