<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_pens', function (Blueprint $table) {
            $table->id();
            $table->string('pen_code', 100)->unique('uq_colony_pens_code');
            $table->string('colony_type', 100);
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_pens');
    }
};
