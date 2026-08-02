<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cert_rsa_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('sys_users')->cascadeOnDelete();
            $table->string('key_identifier', 150)->unique('uq_rsa_keys_identifier');
            $table->text('public_key_pem');
            $table->string('private_key_path')->nullable();
            $table->string('algorithm', 50)->default('RSA');
            $table->integer('key_length');
            $table->string('fingerprint_sha256')->unique('uq_rsa_keys_fingerprint');
            $table->boolean('is_active')->default(true);
            $table->string('key_status', 30)->default('active')->index('idx_cert_rsa_keys_status');
            $table->timestamp('retired_at')->nullable();
            $table->timestamp('compromised_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('status_reason', 255)->nullable();
            $table->timestamps();
            $table->index('user_id', 'idx_cert_rsa_keys_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cert_rsa_keys');
    }
};
