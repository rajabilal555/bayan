<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reciter_id')->constrained()->restrictOnDelete();
            $table->foreignId('background_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('surah');
            $table->unsignedSmallInteger('ayah_start');
            $table->unsignedSmallInteger('ayah_end');
            $table->string('orientation');
            $table->boolean('show_arabic')->default(true);
            $table->boolean('show_translation')->default(false);
            $table->foreignId('translation_source_id')->nullable()->constrained()->nullOnDelete();
            $table->json('arabic_style');
            $table->json('translation_style');
            $table->boolean('include_bismillah')->default(false);
            $table->boolean('loop_background')->default(true);
            $table->string('fingerprint', 64)->index();
            $table->boolean('cache_hit')->default(false);
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->string('output_path')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_jobs');
    }
};
