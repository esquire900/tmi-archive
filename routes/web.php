<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\TalkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// Talks
Route::get('/talk', [TalkController::class, 'index'])->name('talks.index');
Route::get('/talk/{talk}', [TalkController::class, 'show'])->name('talks.show');
Route::get('/bulk-download', [TalkController::class, 'bulkDownload'])->name('talks.bulk-download');

// Playlists
Route::get('/playlist', [PlaylistController::class, 'index'])->name('playlists.index');
Route::get('/playlist/{playlist}', [PlaylistController::class, 'show'])->name('playlists.show');

// Media + transcript endpoints (replace the removed /api/v1/* routes).
// Throttled to blunt automated scraping / download spam.
Route::middleware('throttle:downloads')->group(function () {
    Route::get('/talks/{talk}/download', [MediaController::class, 'download'])->name('talks.download');
    Route::get('/talks/{talk}/download/original', [MediaController::class, 'downloadOriginal'])->name('talks.download.original');
    Route::get('/talks/{talk}/transcription', [MediaController::class, 'transcription'])->name('talks.transcription');
    Route::get('/talks/{talk}/transcription.json', [MediaController::class, 'transcriptionJson'])->name('talks.transcription.json');
});
