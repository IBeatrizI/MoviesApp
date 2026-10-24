<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFavorites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
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
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function register(Request $request): JsonResponse
    {
        if (!$request->username) {
            return response()->json([
                'code' => "4",
                'message' => "Username is missing",
            ], 400);
        }
        if (!$request->email) {
            return response()->json([
                'code' => "5",
                'message' => "Email is missing",
            ], 400);
        } else if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'code' => "10",
                'message' => "Email not valid",
            ], 400);
        }
        if (!$request->password) {
            return response()->json([
                'code' => "6",
                'message' => "Password is missing",
            ], 400);
        }
        $check_username = DB::table('users')
            ->where('email', $request->email)
            ->get();
        if ($check_username->isNotEmpty()) {
            return response()->json([
                'code' => "18",
                'message' => "Email already exists",
            ], 400);
        }
        $check_email = DB::table('users')
            ->where('username', $request->username)
            ->get();
        if ($check_email->isNotEmpty()) {
            return response()->json([
                'code' => "19",
                'message' => "Username already exists",
            ], 400);
        }
        if ($check_email->isEmpty() && $check_username->isEmpty()) {
            $user = new User;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);   // nunca guardar a password em texto simples
            $success = $user->save();
            if ($success) {
                Auth::login($user);
                $request->session()->regenerate();
                return response()->json([
                    'code' => "8",
                    'message' => "User register success",
                    'user' => $user,
                ], 200);
            } else {
                return response()->json([
                    'code' => "9",
                    'message' => "User register failed",
                ], 400);
            }
        } else {
            return response()->json([
                'code' => "7",
                'message' => "User already exists",
            ],
            );
        }
    }

    public function login(Request $request): JsonResponse
    {
        if (!$request->username) {
            return response()->json([
                'code' => "4",
                'message' => "Username is missing",
            ], 400);
        }
        if (!$request->password) {
            return response()->json([
                'code' => "6",
                'message' => "Password is missing",
            ], 400);
        }
        // Procura por username OU email (agrupado) e só depois valida a password com hash
        $user = User::where(function ($q) use ($request) {
            $q->where('username', $request->username)->orWhere('email', $request->username);
        })->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'code' => "11",
                'message' => "Login efetuado com sucesso",
                'user' => $user   // $hidden do modelo esconde a password
            ], 200);
        } else {
            return response()->json([
                'code' => "12",
                'message' => "Verify username and password",
            ], 400);
        }
    }

    public function saveToFavorites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
        ]);

        UserFavorites::firstOrCreate([
            'user_id' => $request->user()->id,
            'movie_id' => $validated['movie_id'],
        ]);

        return response()->json([
            'code' => "15",
            'message' => "Movie saved successfully",
        ]);
    }

    public function deleteFromFavorites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer',
        ]);

        DB::table('user_favorites')
            ->where('user_id', $request->user()->id)
            ->where('movie_id', $validated['movie_id'])
            ->delete();

        return response()->json([
            'code' => "17",
            'message' => "Movie favorite deleted",
        ]);
    }

    public function getFavorites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => 'required|in:pt,en,es',
        ]);

        $favoriteIds = DB::table('user_favorites')
         ->where('user_id', $request->user()->id)
         ->pluck('movie_id');

        $movies = DB::table('movies')
            ->where('language', $validated['language'])
            ->whereIn('id', $favoriteIds)
            ->get();

        $movies = $movies->map(function ($movie) {
            $movie->genre_ids = unserialize(
                $movie->genre_ids,
                ['allowed_classes' => false]
            );
         return $movie;
        });

        return response()->json([
            'code' => "17",
            'message' => "Success",
            'results' => $movies,
        ]);
    }

    public function findFavorites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer',
        ]);

        $exists = DB::table('user_favorites')
            ->where('user_id', $request->user()->id)
            ->where('movie_id', $validated['movie_id'])
            ->exists();

        return response()->json([
            'code' => "0",
            'message' => "Success",
            'result' => $exists,
        ]);
    }
}
