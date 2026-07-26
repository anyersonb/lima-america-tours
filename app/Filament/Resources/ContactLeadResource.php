<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactLeadResource\Pages;
use App\Models\ContactLead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactLeadResource extends Resource
{
    protected static ?string $model = ContactLead::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Mensajes';

    protected static ?string $modelLabel = 'Mensaje';

    protected static ?string $pluralModelLabel = 'Mensajes';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('lastname')
                    ->label('Apellido')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->label('Mensaje')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('source')
                    ->label('Origen')
                    ->required()
                    ->maxLength(255)
                    ->default('contact_form'),
                Forms\Components\TextInput::make('locale')
                    ->label('Idioma')
                    ->required()
                    ->maxLength(5)
                    ->default('es'),
                Forms\Components\TextInput::make('ip')
                    ->label('IP')
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_agent')
                    ->label('Navegador')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_read')
                    ->label('Leído')
                    ->required(),
                Forms\Components\Toggle::make('is_archived')
                    ->label('Archivado')
                    ->required(),
            ]);
    }

    /**
     * Vista de solo lectura para "leer" un mensaje (docs/qa/F7-personas.md
     * §labels #1): antes la única pantalla disponible era el formulario de
     * EDICIÓN completo, con IP/Navegador/Origen como campos de texto
     * editables — se sentía como "editar la computadora del cliente" en vez
     * de leer una carta. Aquí el mensaje del cliente va primero y en texto
     * plano; los datos técnicos quedan aparte, colapsados y claramente
     * etiquetados como informativos.
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Mensaje del cliente')
                ->columns(2)
                ->schema([
                    TextEntry::make('full_name')->label('Nombre completo'),
                    TextEntry::make('email')->label('Correo')->copyable(),
                    TextEntry::make('phone')->label('Teléfono')->copyable()->placeholder('—'),
                    TextEntry::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i'),
                    TextEntry::make('message')->label('Mensaje')->columnSpanFull(),
                ]),
            InfolistSection::make('Información técnica (solo informativa)')
                ->description('Datos de contexto capturados automáticamente; no forman parte del mensaje y no se pueden editar aquí.')
                ->collapsible()
                ->collapsed()
                ->columns(3)
                ->schema([
                    TextEntry::make('source')->label('Origen'),
                    TextEntry::make('locale')->label('Idioma'),
                    TextEntry::make('ip')->label('IP')->placeholder('—'),
                    TextEntry::make('user_agent')->label('Navegador')->placeholder('—')->columnSpanFull(),
                    IconEntry::make('is_read')->label('Leído')->boolean(),
                    IconEntry::make('is_archived')->label('Archivado')->boolean(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label('Apellido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Origen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('locale')
                    ->label('Idioma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_read')
                    ->label('Leído')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_archived')
                    ->label('Archivado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactLeads::route('/'),
            'create' => Pages\CreateContactLead::route('/create'),
            'view' => Pages\ViewContactLead::route('/{record}'),
        ];
    }
}
