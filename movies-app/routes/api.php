
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GenresController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SeriesController;

Route::get('/genres', [GenresController::class, 'getGenres']);

Route::get('/movie', [MovieController::class, 'movies']);

Route::get('/movie/genres', [MovieController::class, 'moviesInGenre']);

Route::get('/series', [SeriesController::class, 'series']);

Route::get('/series/genres', [SeriesController::class, 'genres']);
