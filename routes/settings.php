<?php

use App\Livewire\Settings\Tags\TagIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function (): void {
    Route::get('tags', TagIndex::class)->name('tags.index');
});
