<?php

declare(strict_types=1);

use App\Domains\Chapters\Controllers\ChapterController;
use App\Domains\Mangas\Controllers\MangaController;
use App\Domains\Users\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Manga routes
    Route::get('/manga', [MangaController::class, 'index']);
    Route::get('/manga/search', [MangaController::class, 'search']);
    Route::get('/manga/{id}', [MangaController::class, 'show']);

    // Chapter routes
    Route::get('/chapters/{id}/pages', [ChapterController::class, 'showPages']);
});

