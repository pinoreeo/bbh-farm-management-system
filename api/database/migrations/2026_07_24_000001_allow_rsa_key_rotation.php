<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('cert_rsa_keys', 'idx_cert_rsa_keys_user')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->index('user_id', 'idx_cert_rsa_keys_user');
            });
        }

        if ($this->indexExists('cert_rsa_keys', 'uq_cert_rsa_keys_user')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->dropUnique('uq_cert_rsa_keys_user');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('cert_rsa_keys', 'idx_cert_rsa_keys_user')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->dropIndex('idx_cert_rsa_keys_user');
            });
        }

        if (! $this->hasDuplicateUserKeys() && ! $this->indexExists('cert_rsa_keys', 'uq_cert_rsa_keys_user')) {
            Schema::table('cert_rsa_keys', function (Blueprint $table) {
                $table->unique('user_id', 'uq_cert_rsa_keys_user');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $item) {
                if (($item->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $indexes = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$index]);

            return count($indexes) > 0;
        }

        return Schema::hasTable($table);
    }

    private function hasDuplicateUserKeys(): bool
    {
        return DB::table('cert_rsa_keys')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
