<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_activity_logs', function (Blueprint $table) {
            $table->index(['module', 'subject_id', 'created_at'], 'idx_admin_activity_subject_time');
        });
    }

    public function down(): void
    {
        Schema::table('sys_activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_admin_activity_subject_time');
        });
    }
};
