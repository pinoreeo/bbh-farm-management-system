<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_users', function (Blueprint $table) {
            if (! Schema::hasColumn('sys_users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('sys_users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });

        DB::table('sys_users')
            ->select(['id', 'name', 'first_name', 'last_name'])
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                if ($user->first_name || $user->last_name) {
                    return;
                }

                $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

                DB::table('sys_users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $parts[0] ?? $user->name,
                        'last_name' => $parts[1] ?? null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('sys_users', function (Blueprint $table) {
            if (Schema::hasColumn('sys_users', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('sys_users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
