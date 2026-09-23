<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_pen_movements', function (Blueprint $table) {
            $table->foreignId('to_pen_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('animal_pen_movements', function (Blueprint $table) {
            $table->foreignId('to_pen_id')->nullable(false)->change();
        });
    }
};
