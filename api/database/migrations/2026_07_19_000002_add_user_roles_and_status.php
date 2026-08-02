<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index('role', 'idx_sys_users_role');
            $table->index('is_active', 'idx_sys_users_is_active');
        });
    }

    public function down(): void
    {
        Schema::table('sys_users', function (Blueprint $table) {
            $table->dropIndex('idx_sys_users_role');
            $table->dropIndex('idx_sys_users_is_active');
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
