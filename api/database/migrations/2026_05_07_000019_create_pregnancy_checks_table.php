<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breeding_female_id')->constrained('breed_females');
            $table->foreignId('breeding_period_id')->constrained('breed_periods');
            $table->foreignId('female_animal_id')->constrained('animals');
            $table->date('check_date');
            $table->boolean('is_pregnant');
            $table->string('outcome_status', 30)->nullable();
            $table->string('method', 100)->nullable();
            $table->integer('estimated_gestation_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['breeding_period_id', 'female_animal_id', 'check_date'], 'uq_preg_checks');
            $table->unique(['breeding_female_id', 'check_date'], 'uq_pregnancy_checks_breeding_female_date');
            $table->index('breeding_female_id', 'idx_pregnancy_checks_breeding_female');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_pregnancies');
    }
};
