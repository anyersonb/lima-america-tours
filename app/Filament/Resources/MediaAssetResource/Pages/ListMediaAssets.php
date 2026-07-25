<?php

namespace App\Filament\Resources\MediaAssetResource\Pages;

use App\Filament\Resources\MediaAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    // Filament's default getTitle() runs the plural model label through
    // Str::title(), turning "Biblioteca de medios" into "Biblioteca De
    // Medios" (title-cases every word, English-style). Pin the title as-is.
    protected static ?string $title = 'Biblioteca de medios';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
