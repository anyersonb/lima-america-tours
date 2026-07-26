<?php

namespace App\Filament\Resources\ContactLeadResource\Pages;

use App\Filament\Resources\ContactLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * Reemplaza a la antigua EditContactLead (docs/qa/F7-personas.md §labels #1 /
 * §g #9): "leer" un mensaje de contacto ahora es una vista de solo lectura
 * (Infolist, ver ContactLeadResource::infolist()) en vez de un formulario de
 * edición con IP/Navegador/Origen editables. Abrir esta página también marca
 * el mensaje como leído automáticamente (ver mount()).
 */
class ViewContactLead extends ViewRecord
{
    protected static string $resource = ContactLeadResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_archived')
                ->label(fn (): string => $this->record->is_archived ? 'Restaurar' : 'Archivar')
                ->icon(fn (): string => $this->record->is_archived ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-archive-box')
                ->color(fn (): string => $this->record->is_archived ? 'gray' : 'danger')
                ->requiresConfirmation(fn (): bool => ! $this->record->is_archived)
                ->action(function (): void {
                    $this->record->update(['is_archived' => ! $this->record->is_archived]);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
