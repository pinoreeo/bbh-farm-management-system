<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('sys_users')->nullOnDelete();
            $table->string('admin_name')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('action', 80);
            $table->string('module', 80);
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'created_at'], 'idx_admin_activity_admin_time');
            $table->index(['module', 'created_at'], 'idx_admin_activity_module_time');
            $table->index(['action', 'created_at'], 'idx_admin_activity_action_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_activity_logs');
    }
};
