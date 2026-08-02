<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('tag_number', 100)->unique('uq_animals_tag');
            $table->foreignId('breed_id')->constrained('animal_breeds');
            $table->enum('sex', ['male', 'female']);
            $table->string('generation', 30);
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->enum('life_status', ['alive', 'dead'])->default('alive');
            $table->text('notes')->nullable();
            $table->boolean('is_impor')->default(false);
            $table->string('origin_type', 50)->default('unknown');
            $table->string('origin_detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
