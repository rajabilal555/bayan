<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('category')->index();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('source')->default('pexels');
            $table->string('attribution')->nullable();
            $table->boolean('loop_default')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backgrounds');
    }
};
