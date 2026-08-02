<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Contenido';

    protected static ?string $navigationLabel = 'Testimonios';

    protected static ?string $modelLabel = 'Testimonio';

    protected static ?string $pluralModelLabel = 'Testimonios';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Autor')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label('País')
                    ->maxLength(255),
                Forms\Components\TextInput::make('avatar')
                    ->label('Avatar (URL)')
                    ->maxLength(255),
                Forms\Components\Textarea::make('quote_es')
                    ->required()
                    ->label('Comentario (Español)')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('quote_en')
                    ->label('Comment (English)')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('quote_pt')
                    ->label('Comentário (Português)')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('rating')
                    ->label('Calificación')
                    ->required()
                    ->numeric()
                    ->default(5.0),
                Forms\Components\DatePicker::make('reviewed_at')
                    ->label('Fecha de la reseña')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->helperText('Se muestra en la portada como "Nombre · fecha · tour". Si se deja vacía, se usa la fecha de hoy.'),
                Forms\Components\TextInput::make('source')
                    ->label('Origen')
                    ->required()
                    ->maxLength(255)
                    ->default('Google'),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Destacado')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aprobado')
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('tour_id')
                    ->label('Tour')
                    ->relationship('tour', 'title_es')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Autor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quote_es')
                    ->label('Comentario')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tour.title_es')
                    ->label('Tour')
                    ->limit(28)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('★')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Fecha reseña')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aprobado'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Aprobados')
                    ->falseLabel('Pendientes'),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Origen')
                    ->options([
                        'Web' => 'Web (enviadas por usuarios)',
                        'Google' => 'Google',
                        'Tripadvisor' => 'Tripadvisor',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
