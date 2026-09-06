<?php

use App\Http\Controllers\JournalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [JournalController::class, 'index'])->name('journals.index');

Route::prefix('journals')->name('journals.')->group(function () {
    Route::post('/', [JournalController::class, 'store'])->name('store');
    Route::get('/{journal}', [JournalController::class, 'show'])->name('show');
    Route::match(['post', 'put'], '/{journal}', [JournalController::class, 'update'])->name('update');
    Route::delete('/{journal}', [JournalController::class, 'destroy'])->name('destroy');
});

Route::get('/photos/{photo}', [JournalController::class, 'servePhoto'])->name('photos.show');
