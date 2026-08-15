<?php

namespace App\Filament\Resources\RenderJobs\Tables;

use App\Enums\RenderStatus;
use App\Jobs\RenderVideoJob;
use App\Models\RenderCache;
use App\Models\RenderJob;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class RenderJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->searchable(),
                TextColumn::make('surah')->formatStateUsing(
                    fn (RenderJob $record) => $record->surah.':'.$record->ayah_start.'–'.$record->ayah_end
                ),
                TextColumn::make('reciter.name'),
                TextColumn::make('status')->badge(),
                IconColumn::make('cache_hit')->boolean(),
                TextColumn::make('error')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(RenderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->value])),
            ])
            ->recordActions([
                Action::make('retry')
                    ->visible(fn (RenderJob $record) => $record->status === RenderStatus::Failed)
                    ->action(function (RenderJob $record): void {
                        $record->update(['status' => RenderStatus::Pending, 'error' => null]);
                        RenderVideoJob::dispatch($record->id);
                    }),
                Action::make('bustCache')
                    ->label('Bust cache')
                    ->requiresConfirmation()
                    ->visible(fn (RenderJob $record) => filled($record->fingerprint))
                    ->action(function (RenderJob $record): void {
                        $cache = RenderCache::query()->where('fingerprint', $record->fingerprint)->first();

                        if ($cache === null) {
                            return;
                        }

                        $disk = Storage::disk(config('bayan.disks.renders'));

                        if ($disk->exists($cache->output_path)) {
                            $disk->delete($cache->output_path);
                        }

                        $cache->delete();
                    }),
                Action::make('download')
                    ->visible(fn (RenderJob $record) => $record->status === RenderStatus::Completed && filled($record->output_path))
                    ->url(fn (RenderJob $record) => URL::temporarySignedRoute(
                        'renders.download',
                        now()->addMinutes(30),
                        ['job' => $record],
                    ))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('id', 'desc');
    }
}
