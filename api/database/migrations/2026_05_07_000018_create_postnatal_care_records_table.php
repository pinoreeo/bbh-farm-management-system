<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('med_postnatal_cares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offspring_birth_id')->constrained('breed_offsprings');
            $table->foreignId('birth_event_id')->constrained('breed_births');
            $table->foreignId('target_animal_id')->constrained('animals');
            $table->date('care_date');
            $table->string('administration_method', 100)->nullable();
            $table->decimal('volume_ml', 8, 2)->nullable();
            $table->string('navel_iodine_status', 50)->nullable();
            $table->decimal('vitamin_ade_ml', 8, 2)->nullable();
            $table->decimal('vitamin_b_complex_ml', 8, 2)->nullable();
            $table->decimal('intracin_ml', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['birth_event_id', 'target_animal_id'], 'postnatal_event_animal_unique');
            $table->unique('offspring_birth_id', 'uq_postnatal_care_offspring_birth');
            $table->index('offspring_birth_id', 'idx_postnatal_offspring_birth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('med_postnatal_cares');
    }
};
