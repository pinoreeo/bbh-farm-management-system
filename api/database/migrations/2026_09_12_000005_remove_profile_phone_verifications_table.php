<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sys_profile_phone_verifications');
    }

    public function down(): void
    {
        // Tabel ini hanya digunakan oleh alur verifikasi profil yang sudah digantikan.
    }
};
