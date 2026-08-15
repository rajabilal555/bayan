<?php

namespace App\Filament\App\Resources\RenderJobs\Pages;

use App\Filament\App\Resources\RenderJobs\RenderJobResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageRenderJobs extends ManageRecords
{
    protected static string $resource = RenderJobResource::class;

    public function getHeading(): string|Htmlable
    {
        return 'My renders';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Watch a video here once it is ready. You can still download a copy.';
    }
}
