<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colony_pen_id')->constrained('animal_pens');
            $table->string('period_code', 100);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('male_animal_id')->constrained('animals');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['colony_pen_id', 'period_code'], 'uq_breeding_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_periods');
    }
};
