<?php

use App\Filament\App\Pages\CreateVideo;
use App\Models\Background;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('app');
});

test('create form shows signed background thumbnail previews', function () {
    $disk = Storage::disk((string) config('bayan.disks.library'));
    $path = 'backgrounds/test-preview-'.uniqid().'.jpg';
    $disk->put($path, 'thumb');

    try {
        $user = User::factory()->create();
        $background = Background::factory()->create([
            'label' => 'Ocean swell',
            'category' => 'ocean',
            'thumbnail_path' => $path,
        ]);

        $url = $background->thumbnailUrl();

        expect($url)->toContain('signature=');

        $this->get($url)->assertOk();
        $this->get('/library/'.$path)->assertForbidden();

        Livewire::actingAs($user)
            ->test(CreateVideo::class)
            ->assertSuccessful()
            ->assertSee('Ocean swell')
            ->assertSeeHtml('<img')
            ->assertSee('signature=');
    } finally {
        $disk->delete($path);
    }
});
