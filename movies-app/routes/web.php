
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SeriesController;

Route::view('/', 'catalog');

Route::prefix('api')->group(function () {

    // Autenticação
    Route::post('/user/register', [UserController::class, 'register']);
    Route::post('/user/login', [UserController::class, 'login']);

    // Consultar a sessão atual
    Route::get('/user/me', function (Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    });

    // Apenas utilizadores autenticados
    Route::middleware('auth')->group(function () {

        Route::post('/user/logout', function (Request $request) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Sessão terminada.'
            ]);
        });

        // Favoritos de filmes
        Route::post('/user/favorites/save', [UserController::class, 'saveToFavorites']);
        Route::post('/user/favorites/delete', [UserController::class, 'deleteFromFavorites']);
        Route::get('/user/favorites', [UserController::class, 'getFavorites']);
        Route::get('/user/favorites/find', [UserController::class, 'findFavorites']);

        // Favoritos de séries
        Route::post('/user/favorites/series/save', [SeriesController::class, 'saveFavorite']);
        Route::post('/user/favorites/series/delete', [SeriesController::class, 'deleteFavorite']);
        Route::get('/user/favorites/series', [SeriesController::class, 'getFavorites']);
    });
});
