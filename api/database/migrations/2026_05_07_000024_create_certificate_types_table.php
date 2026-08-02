<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cert_types', function (Blueprint $table) {
            $table->id();
            $table->enum('type_code', ['BIBIT_UNGGUL', 'KELAHIRAN', 'KEMATIAN'])->unique('uq_certificate_types_code');
            $table->string('type_name')->nullable();
            $table->text('description')->nullable();
            $table->string('template_version', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cert_types');
    }
};
