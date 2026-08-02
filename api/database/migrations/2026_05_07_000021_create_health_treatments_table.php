<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('med_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals');
            $table->string('treatment_group', 100);
            $table->string('product_name');
            $table->date('treatment_date')->index('idx_health_treatments_date');
            $table->string('dosage', 100)->nullable();
            $table->string('administration_route', 100)->nullable();
            $table->string('action_category', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('med_treatments');
    }
};
