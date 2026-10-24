<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GenreWithMovies;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Movie $movie)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Movie $movie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Movie $movie)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movie $movie)
    {
        //
    }

    public function movies(Request $request): JsonResponse
    {

        $language = $request->language;
        if (!$language) {
            return response()->json([
                'code' => "1",
                'message' => "Language query is missing",
            ], 400);
        }
        $page = $request->page;
        if (!$page) {
            return response()->json([
                'code' => "2",
                'message' => "Page number query is missing",
            ], 400);
        }
        $items_per_page = 25;
        // 0 representa "Todos os géneros"; mantém a compatibilidade com IDs existentes.
        $genre = filter_var($request->query('with_genres', '0'), FILTER_VALIDATE_INT);
        if ($genre === false || $genre < 0) {
            return response()->json([
                'code' => "3",
                'message' => "Genre query is invalid",
            ], 400);
        }
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $items_per_page;   // página 1 -> 0, página 2 -> 25, ...
        $query = DB::table('movies')->where('language', $language);
        if ($genre > 0) {
            // genre_ids continua no formato serializado original para compatibilidade.
            $query->where('genre_ids', 'like', "%;i:$genre;%");
        }

        $quantity = (clone $query)->count();
        $movies = $query->orderBy('popularity', 'desc')
            ->offset($offset)->limit($items_per_page)->get();

        return response()->json([
            'page' => $page,
            'total_pages' => (int) ceil($quantity / $items_per_page),
            'results' => $movies->map(function ($value) {
                $value->genre_ids = unserialize($value->genre_ids);
                return $value;
            }),
        ]);
    }

    public function moviesInGenre(Request $request): JsonResponse
    {
        $language = $request->language;
        if (!$language) {
            return response()->json([
                'code' => "1",
                'message' => "Language query is missing",
            ], 400);
        }
        $items_per_page = 10; // alterar caso queiras mais items por categoria

        $genres_array = DB::table('genres')->where('language', $language)->get();

        $results = array();

        foreach ($genres_array as $tempGenre) {
            $genre_name = $tempGenre->name;
            $genre_id = $tempGenre->id;
            $movies = DB::table('movies')
                ->where("language", $language)
                ->where('genre_ids', "like", "%;i:$genre_id;%")
                ->orderBy('popularity', 'desc')
                ->take($items_per_page)
                ->get()->map(function ($value) {
                    $value->genre_ids = unserialize($value->genre_ids);
                    return $value;
                });
            $genre_with_movies = new GenreWithMovies();
            $genre_with_movies->genre_name = $genre_name;
            $genre_with_movies->movie_list = $movies;
            $genre_with_movies->genre_id = $genre_id;
            array_push($results, $genre_with_movies);
        }


        return response()->json([
            'results' => $results,
        ]);
    }
}
