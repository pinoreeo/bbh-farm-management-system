<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals');
            $table->foreignId('certificate_type_id')->constrained('cert_types');
            $table->string('certificate_number', 150)->unique('uq_certificates_certno');
            $table->string('verification_token', 100)->unique();
            $table->date('issue_date');
            $table->string('issue_place')->nullable();
            $table->foreignId('birth_event_id')->nullable()->constrained('breed_births');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('death_date')->nullable();
            $table->time('death_time')->nullable();
            $table->string('cause_of_death')->nullable();
            $table->string('barcode_value')->nullable();
            $table->string('barcode_format', 50)->nullable();
            $table->text('payload_snapshot');
            $table->string('canonical_method', 100)->default('canonical-json');
            $table->string('hash_sha256');
            $table->string('official_pdf_path')->nullable();
            $table->string('official_pdf_hash_sha256', 64)->nullable()->index();
            $table->text('official_pdf_signature_base64')->nullable();
            $table->string('official_pdf_signature_scheme', 100)->nullable();
            $table->foreignId('official_pdf_rsa_key_id')->nullable()->constrained('cert_rsa_keys');
            $table->dateTime('official_pdf_signed_at')->nullable();
            $table->dateTime('official_pdf_generated_at')->nullable();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active')->index('idx_certificates_status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certs');
    }
};
