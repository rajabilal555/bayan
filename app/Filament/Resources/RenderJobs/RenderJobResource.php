<?php

namespace App\Filament\Resources\RenderJobs;

use App\Filament\Resources\RenderJobs\Pages\ListRenderJobs;
use App\Filament\Resources\RenderJobs\Tables\RenderJobsTable;
use App\Models\RenderJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RenderJobResource extends Resource
{
    protected static ?string $model = RenderJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Renders';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return RenderJobsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRenderJobs::route('/'),
        ];
    }
}
