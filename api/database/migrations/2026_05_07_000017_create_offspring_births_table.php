<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_offsprings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('birth_event_id')->constrained('breed_births');
            $table->foreignId('offspring_animal_id')->constrained('animals');
            $table->decimal('birth_weight_kg', 6, 2);
            $table->enum('birth_status', ['alive', 'dead'])->default('alive');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['birth_event_id', 'offspring_animal_id'], 'uq_offspring_births');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_offsprings');
    }
};
