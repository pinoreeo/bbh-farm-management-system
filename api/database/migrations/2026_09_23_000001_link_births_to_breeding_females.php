<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breed_births', function (Blueprint $table) {
            $table->foreignId('breeding_female_id')->nullable()->unique()->constrained('breed_females');
        });
    }

    public function down(): void
    {
        Schema::table('breed_births', function (Blueprint $table) {
            $table->dropUnique(['breeding_female_id']);
            $table->dropConstrainedForeignId('breeding_female_id');
        });
    }
};
