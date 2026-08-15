<?php

namespace App\Filament\App\Resources\RenderJobs;

use App\Enums\RenderStatus;
use App\Filament\App\Resources\RenderJobs\Pages\ManageRenderJobs;
use App\Models\RenderJob;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RenderJobResource extends Resource
{
    protected static ?string $model = RenderJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static ?string $navigationLabel = 'My renders';

    protected static ?string $pluralModelLabel = 'renders';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with(['reciter']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    View::make('filament.app.tables.render-preview'),
                    TextColumn::make('surah')
                        ->formatStateUsing(
                            fn (RenderJob $record): string => $record->surah.':'.$record->ayah_start.'–'.$record->ayah_end
                        )
                        ->weight(FontWeight::SemiBold)
                        ->label('Range'),
                    TextColumn::make('reciter.name')
                        ->color('gray'),
                    TextColumn::make('status')
                        ->badge()
                        ->formatStateUsing(fn (RenderStatus $state): string => $state->label())
                        ->color(fn (RenderStatus $state): string => match ($state) {
                            RenderStatus::Completed => 'success',
                            RenderStatus::Failed => 'danger',
                            RenderStatus::Processing => 'warning',
                            RenderStatus::Pending => 'gray',
                        }),
                    TextColumn::make('created_at')
                        ->since()
                        ->color('gray'),
                ]),
            ])
            ->recordActions([
                Action::make('download')
                    ->visible(fn (RenderJob $record): bool => $record->isPlayable())
                    ->url(fn (RenderJob $record): ?string => $record->downloadUrl())
                    ->openUrlInNewTab(),
            ])
            ->emptyStateIcon(Heroicon::OutlinedFilm)
            ->emptyStateHeading('No videos yet')
            ->emptyStateDescription('Create a video and it will show up here.')
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRenderJobs::route('/'),
        ];
    }
}
