<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_source_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->text('text');
            $table->timestamps();

            $table->unique(['translation_source_id', 'surah', 'ayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
