<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ayahs\AyahResource;
use App\Filament\Resources\Backgrounds\BackgroundResource;
use App\Filament\Resources\Reciters\ReciterResource;
use App\Filament\Resources\Surahs\SurahResource;
use App\Filament\Resources\TranslationSources\TranslationSourceResource;
use App\Models\Ayah;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\Translation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LibraryStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Surahs', Surah::query()->count())
                ->url(SurahResource::getUrl('index')),
            Stat::make('Ayahs', Ayah::query()->count())
                ->url(AyahResource::getUrl('index')),
            Stat::make('Reciters', Reciter::query()->count())
                ->url(ReciterResource::getUrl('index')),
            Stat::make('Translations', Translation::query()->count())
                ->url(TranslationSourceResource::getUrl('index')),
            Stat::make('Backgrounds', Background::query()->count())
                ->url(BackgroundResource::getUrl('index')),
        ];
    }
}
