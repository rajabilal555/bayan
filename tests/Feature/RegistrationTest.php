<?php

use App\Filament\App\Pages\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('app');
});

test('users can register with a real email domain', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Bilal',
            'email' => 'bilal@example.com',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'bilal@example.com')->first())
        ->not->toBeNull()
        ->disposable_check_passed->toBeTrue();
});

test('disposable emails are rejected', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Temp',
            'email' => 'user@mailinator.com',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertHasFormErrors(['email']);
});
