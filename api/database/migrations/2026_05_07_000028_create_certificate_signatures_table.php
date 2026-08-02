<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cert_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained('certs');
            $table->foreignId('rsa_key_id')->constrained('cert_rsa_keys');
            $table->foreignId('signed_by_user_id')->constrained('sys_users');
            $table->string('signature_scheme', 100);
            $table->text('signature_base64');
            $table->dateTime('signed_at');
            $table->enum('status', ['active', 'inactive', 'superseded'])->default('active')->index('idx_certificate_signatures_status');
            $table->timestamps();

            $table->index('certificate_id', 'idx_certificate_signatures_cert');
            $table->index(['certificate_id', 'status'], 'idx_certificate_signatures_cert_status');
            $table->index('signed_by_user_id', 'idx_certificate_signatures_signed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cert_signatures');
    }
};
