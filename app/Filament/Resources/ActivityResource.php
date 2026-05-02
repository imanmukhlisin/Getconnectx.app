<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

class ActivityResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        return parent::getEloquentQuery()->whereRaw('1=0');
    }
}
