<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('med_treatments', function (Blueprint $table) {
            $table->unique(['animal_id', 'treatment_group', 'product_name', 'treatment_date'], 'uq_med_treatments_identity');
        });
        Schema::table('med_vaccinations', function (Blueprint $table) {
            $table->unique(['animal_id', 'category_name', 'vaccination_date', 'product_name'], 'uq_med_vaccinations_identity');
        });
    }

    public function down(): void
    {
        Schema::table('med_vaccinations', function (Blueprint $table) {
            $table->dropUnique('uq_med_vaccinations_identity');
        });
        Schema::table('med_treatments', function (Blueprint $table) {
            $table->dropUnique('uq_med_treatments_identity');
        });
    }
};
