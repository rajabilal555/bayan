<?php

use App\Filament\App\Pages\CreateVideo;
use App\Filament\App\Resources\RenderJobs\Pages\ManageRenderJobs;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('app');
});

test('guests are sent to the filament login page', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertOk();
    $this->get('/register')->assertOk();
});

test('unverified users can use the app panel', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Create a video')
        ->assertSee('My renders')
        ->assertDontSee('Dashboard');

    $this->actingAs($user)
        ->get(CreateVideo::getUrl())
        ->assertOk();

    Livewire::actingAs($user)
        ->test(CreateVideo::class)
        ->assertSuccessful();

    Livewire::actingAs($user)
        ->test(ManageRenderJobs::class)
        ->assertSuccessful();
});

test('non-admins cannot open the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('admins can open the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});
