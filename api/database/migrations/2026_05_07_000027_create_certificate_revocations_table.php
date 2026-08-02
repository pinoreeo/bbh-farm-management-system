<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cert_revocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->unique('uq_certificate_revocations_cert')->constrained('certs');
            $table->dateTime('revoked_at');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cert_revocations');
    }
};
