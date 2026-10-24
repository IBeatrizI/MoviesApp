<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // As séries não estão na tabela "movies" (vêm da TMDB), por isso têm uma tabela de favoritos própria
        Schema::create('user_favorite_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('series_id');
            $table->unique(['user_id', 'series_id']);   // impede guardar a mesma série duas vezes
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorite_series');
    }
};
