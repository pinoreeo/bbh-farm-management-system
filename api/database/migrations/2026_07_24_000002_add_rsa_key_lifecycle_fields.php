<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cert_rsa_keys', 'key_status')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->string('key_status', 30)->default('active')->after('is_active')->index('idx_cert_rsa_keys_status');
                $table->timestamp('retired_at')->nullable()->after('key_status');
                $table->timestamp('compromised_at')->nullable()->after('retired_at');
                $table->timestamp('last_used_at')->nullable()->after('compromised_at');
                $table->string('status_reason', 255)->nullable()->after('last_used_at');
            });
        }

        DB::table('cert_rsa_keys')
            ->where('is_active', 0)
            ->update([
                'key_status' => 'retired',
                'retired_at' => DB::raw($this->currentTimestampExpression()),
                'status_reason' => 'Retired sebelum lifecycle status tersedia.',
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('cert_rsa_keys', 'key_status')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->dropIndex('idx_cert_rsa_keys_status');
                $table->dropColumn([
                    'key_status',
                    'retired_at',
                    'compromised_at',
                    'last_used_at',
                    'status_reason',
                ]);
            });
        }
    }

    private function currentTimestampExpression(): string
    {
        return DB::getDriverName() === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
    }
};
