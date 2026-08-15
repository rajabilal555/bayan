<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->text('uthmani_text');
            $table->timestamps();

            $table->unique(['surah', 'ayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayahs');
    }
};
