<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cert_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained('certs');
            $table->string('verification_method', 50)->nullable();
            $table->dateTime('verification_time')->index('idx_cvl_time');
            $table->boolean('is_valid');
            $table->string('certificate_status_at_verification', 50);
            $table->text('failure_reason')->nullable();
            $table->string('used_key_fingerprint')->nullable();
            $table->string('used_barcode_value')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cert_log');
    }
};
