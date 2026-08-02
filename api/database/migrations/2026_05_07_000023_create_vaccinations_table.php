<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('med_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals');
            $table->string('category_name', 100);
            $table->date('vaccination_date');
            $table->string('product_name');
            $table->string('dosage', 100)->nullable();
            $table->string('administration_route', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'category_name', 'vaccination_date'], 'idx_med_vacc_animal_category_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('med_vaccinations');
    }
};
