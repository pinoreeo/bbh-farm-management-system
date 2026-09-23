<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_users', function (Blueprint $table): void {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        Schema::create('sys_admin_phone_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('sys_users')->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_admin_phone_verifications');

        Schema::table('sys_users', function (Blueprint $table): void {
            $table->dropColumn('phone_verified_at');
        });
    }
};
