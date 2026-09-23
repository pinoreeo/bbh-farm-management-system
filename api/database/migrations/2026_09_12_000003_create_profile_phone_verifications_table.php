<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_profile_phone_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('sys_users')->cascadeOnDelete();
            $table->string('phone', 100);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_profile_phone_verifications');
    }
};
