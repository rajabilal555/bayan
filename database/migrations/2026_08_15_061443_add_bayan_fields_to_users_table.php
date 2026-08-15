<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('disposable_check_passed')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->unsignedSmallInteger('daily_render_limit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disposable_check_passed', 'is_admin', 'daily_render_limit']);
        });
    }
};
