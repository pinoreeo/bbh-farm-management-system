<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_pending_admin_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_id')->constrained('sys_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 32);
            $table->string('password_hash');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_pending_admin_registrations');
    }
};
