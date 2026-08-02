<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_females', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breeding_period_id')->constrained('breed_periods');
            $table->foreignId('female_animal_id')->constrained('animals');
            $table->date('entry_date');
            $table->date('exit_date')->nullable();
            $table->string('exit_reason')->nullable();
            $table->timestamps();

            $table->unique(['breeding_period_id', 'female_animal_id'], 'uq_breeding_females');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_females');
    }
};
