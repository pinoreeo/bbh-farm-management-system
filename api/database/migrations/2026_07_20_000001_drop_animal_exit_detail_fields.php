<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'exit_date')) {
                $table->dropColumn('exit_date');
            }

            if (Schema::hasColumn('animals', 'exit_reason')) {
                $table->dropColumn('exit_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'exit_date')) {
                $table->date('exit_date')->nullable()->after('exit_status');
            }

            if (! Schema::hasColumn('animals', 'exit_reason')) {
                $table->string('exit_reason')->nullable()->after('exit_date');
            }
        });
    }
};
