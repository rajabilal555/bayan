<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('render_jobs', function (Blueprint $table) {
            $table->unsignedSmallInteger('credits')->default(1)->after('cache_hit');
        });
    }

    public function down(): void
    {
        Schema::table('render_jobs', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
