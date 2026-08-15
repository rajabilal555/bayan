<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reciters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('qul_resource_id')->unique();
            $table->string('granularity')->default('ayah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reciters');
    }
};
