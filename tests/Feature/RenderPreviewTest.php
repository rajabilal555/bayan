<?php

use App\Enums\RenderStatus;
use App\Filament\App\Resources\RenderJobs\Pages\ManageRenderJobs;
use App\Models\RenderJob;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('app');
    Storage::fake(config('bayan.disks.renders'));
});

test('owners can watch and download a finished video', function () {
    $user = User::factory()->create();
    $job = RenderJob::factory()->for($user)->completed()->create();
    Storage::disk(config('bayan.disks.renders'))->put($job->output_path, 'fake-mp4');

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('renders.stream', now()->addMinutes(5), ['job' => $job]))
        ->assertOk()
        ->assertHeader('content-type', 'video/mp4');

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('renders.download', now()->addMinutes(5), ['job' => $job]))
        ->assertOk();
});

test('other users cannot stream a render', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $job = RenderJob::factory()->for($owner)->completed()->create();
    Storage::disk(config('bayan.disks.renders'))->put($job->output_path, 'fake-mp4');

    $this->actingAs($stranger)
        ->get(URL::temporarySignedRoute('renders.stream', now()->addMinutes(5), ['job' => $job]))
        ->assertForbidden();
});

test('unfinished renders are not streamable', function () {
    $user = User::factory()->create();
    $job = RenderJob::factory()->for($user)->create([
        'status' => RenderStatus::Processing,
    ]);

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('renders.stream', now()->addMinutes(5), ['job' => $job]))
        ->assertNotFound();
});

test('my renders shows a video player and hides cache details', function () {
    $user = User::factory()->create();
    $job = RenderJob::factory()->for($user)->completed()->create();
    Storage::disk(config('bayan.disks.renders'))->put($job->output_path, 'fake-mp4');

    Livewire::actingAs($user)
        ->test(ManageRenderJobs::class)
        ->assertSuccessful()
        ->assertSeeHtml('<video')
        ->assertDontSee('Cached');
});
