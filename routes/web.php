<?php

use App\Http\Controllers\RenderFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'signed'])->group(function (): void {
    Route::get('/renders/{job}', [RenderFileController::class, 'stream'])->name('renders.stream');
    Route::get('/renders/{job}/download', [RenderFileController::class, 'download'])->name('renders.download');
});
