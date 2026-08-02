<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_births', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dam_id')->constrained('animals');
            $table->foreignId('sire_id')->nullable()->constrained('animals');
            $table->date('birth_date');
            $table->time('birth_time')->nullable();
            $table->integer('offspring_count');
            $table->string('birth_process', 100);
            $table->string('dam_grade', 50)->nullable();
            $table->string('birth_place')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_births');
    }
};
