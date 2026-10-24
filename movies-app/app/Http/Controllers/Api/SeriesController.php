<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SeriesController extends Controller
{
    private const LANGUAGES = ['en', 'pt', 'es'];   // mesmos idiomas dos filmes

    // Resposta de erro igual à dos filmes (code 1)
    private function erroIdioma(): JsonResponse
    {
        return response()->json([
            'code' => "1",
            'message' => "Language query is missing or invalid",
        ], 400);
    }

    // GET /api/series/genres?language=pt
    // Os géneros de séries na TMDB são diferentes dos de filmes (outros IDs, ex.: "Action & Adventure")
    public function genres(Request $request): JsonResponse
    {
        $language = $request->language;
        if (!in_array($language, self::LANGUAGES)) {
            return $this->erroIdioma();
        }

        // A lista de géneros quase não muda: cache de 24 horas
        $genres = Cache::remember("series.genres.$language", 86400, function () use ($language) {
            $response = Http::withToken(config('services.tmdb.token'))
                ->acceptJson()
                ->get('https://api.themoviedb.org/3/genre/tv/list', ['language' => $language]);
            $response->throw();
            return $response->json('genres');
        });

        // Mesmo formato que /api/genres dos filmes
        return response()->json(['genres' => $genres]);
    }

    // GET /api/series?language=pt&page=1&with_genres=18
    public function series(Request $request): JsonResponse
    {
        $language = $request->language;
        if (!in_array($language, self::LANGUAGES)) {
            return $this->erroIdioma();
        }
        $page = max(1, (int) $request->page);
        $genre = (int) $request->with_genres;   // 0 = sem filtro; (int) também impede valores estranhos

        $data = Cache::remember("series.$language.$page.$genre", 600, function () use ($language, $page, $genre) {
            $params = [
                'language' => $language,
                'page' => $page,
                'sort_by' => 'popularity.desc',
            ];
            if ($genre > 0) {
                $params['with_genres'] = $genre;
            }
            $response = Http::withToken(config('services.tmdb.token'))
                ->acceptJson()
                ->get('https://api.themoviedb.org/3/discover/tv', $params);
            $response->throw();
            return $response->json();
        });

        return response()->json([
            'page' => $data['page'],
            'total_pages' => min($data['total_pages'], 500),   // a TMDB só serve até à página 500
            'results' => $data['results'],
        ]);
    }

    // Valida user_id e series_id; devolve a resposta de erro, ou null se estiver tudo bem
    private function validarFavorito(Request $request): ?JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Utilizador não autenticado'
            ], 401);
        }

        $request->validate([
            'series_id' => 'required|integer|min:1',
        ]);

        return null;
    }

    // POST /api/user/favorites/series/save  (user_id, series_id)
    
    public function saveFavorite(Request $request): JsonResponse
    {
        if ($erro = $this->validarFavorito($request)) {
            return $erro;
        }

        DB::table('user_favorite_series')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'series_id' => $request->integer('series_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'code' => "15",
            'message' => "Series saved successfully",
        ]);
    }

    public function deleteFavorite(Request $request): JsonResponse
    {
        if ($erro = $this->validarFavorito($request)) {
            return $erro;
        }

        DB::table('user_favorite_series')
            ->where('user_id', $request->user()->id)
            ->where('series_id', $request->integer('series_id'))
            ->delete();

        return response()->json([
            'code' => "17",
            'message' => "Series favorite deleted",
        ]);
    }


    // GET /api/user/favorites/series?user_id=1&language=pt
    public function getFavorites(Request $request): JsonResponse
    {
        $language = $request->language;
        if (!in_array($language, self::LANGUAGES)) {
            return $this->erroIdioma();
        }
        

        $ids = DB::table('user_favorite_series')->where('user_id', $request->user()->id)->pluck('series_id');

        // Só guardamos o ID; os dados (nome, sinopse, cartaz) vêm da TMDB no idioma pedido, com cache de 24 h
        $results = $ids->map(function ($id) use ($language) {
            return Cache::remember("series.detail.$language.$id", 86400, function () use ($language, $id) {
                $response = Http::withToken(config('services.tmdb.token'))
                    ->acceptJson()
                    ->get("https://api.themoviedb.org/3/tv/$id", ['language' => $language]);
                return $response->successful() ? $response->json() : null;   // null = não fica em cache
            });
        })->filter()->values();

        return response()->json(['code' => "17", 'message' => "Success", 'results' => $results], 200);
    }
}
