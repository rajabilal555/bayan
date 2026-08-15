<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayah_audio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reciter_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->string('path');
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            $table->unique(['reciter_id', 'surah', 'ayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayah_audio');
    }
};
