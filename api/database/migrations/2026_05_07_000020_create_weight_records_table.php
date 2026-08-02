<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals');
            $table->date('record_date');
            $table->decimal('weight_kg', 6, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['animal_id', 'record_date'], 'uq_weight_records_animal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_weights');
    }
};
