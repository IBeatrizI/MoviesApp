<?php


use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->integer('id', false, true);
            $table->boolean('adult')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->string('genre_ids')->nullable();
            $table->string('original_language')->nullable();
            $table->string('original_title')->nullable();
            $table->string('overview', 3000)->nullable();
            $table->double('popularity')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('release_date')->nullable();
            $table->string('title')->nullable();
            $table->boolean('video')->nullable();
            $table->double('vote_average')->nullable();
            $table->integer('vote_count')->nullable();
            $table->string("language");
            $table->primary(['id', 'language']);
            $table->timestamps();
        });
        $languages=['en', 'pt', 'es'];
        foreach ($languages as $language) {
            $genres = DB::table('genres')->where('language', $language)->get();
            foreach ($genres as $genre) {
                for ($page = 1; $page <= 15; $page++) {
                    $base = 'https://api.themoviedb.org/3/discover/movie?include_adult=true&include_video=false&sort_by=popularity.desc&page=%d&language=%s&with_genres=%s';
                    $format = sprintf($base, $page, $language, $genre->id);
                    $curl_h = curl_init($format);
                    curl_setopt($curl_h, CURLOPT_HTTPHEADER,
                        array(
                            "Content-Type: application/json",
                            "Authorization: Bearer " . config('services.tmdb.token'),
                        )
                    );
                    curl_setopt($curl_h, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($curl_h);
                    $json_data = json_decode($response,true);
                    foreach ($json_data['results'] as $data) {
                        $checkIfExists = DB::table('movies')->where('language', $language)->where("id", $data['id'])->get();
                        if ($checkIfExists->isEmpty()) {
                            $movie = new Movie;
                            $movie->id = $data['id'];
                            $movie->language = $language;
                            $movie->adult = $data['adult'];
                            $movie->backdrop_path = $data['backdrop_path'];
                            $movie->genre_ids = serialize($data['genre_ids']);
                            $movie->original_language = $data['original_language'];
                            $movie->original_title = $data['original_title'];
                            $movie->overview = $data['overview'];
                            $movie->popularity = $data['popularity'];
                            $movie->poster_path = $data['poster_path'];
                            $movie->release_date = $data['release_date'];
                            $movie->title = $data['title'];
                            $movie->video = $data['video'];
                            $movie->vote_average = $data['vote_average'];
                            $movie->vote_count = $data['vote_count'];
                            $movie->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
