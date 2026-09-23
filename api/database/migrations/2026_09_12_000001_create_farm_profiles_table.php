<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_farm_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('farm_name');
            $table->text('address')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_farm_profiles');
    }
};
