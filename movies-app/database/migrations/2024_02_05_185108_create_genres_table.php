<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->integer('id', false, true);
            $table->string('name');
            $table->string('language');
            $table->primary(['id', 'language']);
            $table->timestamps();
        });

        foreach (['en', 'pt', 'es'] as $lang) {
            $response = Http::withToken(config('services.tmdb.token'))
                ->acceptJson()
                ->get('https://api.themoviedb.org/3/genre/movie/list', ['language' => $lang]);

            $response->throw(); // se a API falhar (ex.: 401), a migração pára com uma mensagem clara

            foreach ($response->json('genres') as $genre) {
                DB::table('genres')->insert([
                    'id'         => $genre['id'],
                    'name'       => $genre['name'],
                    'language'   => $lang,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};