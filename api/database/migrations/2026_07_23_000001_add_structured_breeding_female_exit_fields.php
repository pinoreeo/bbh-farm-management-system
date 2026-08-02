<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breed_females', function (Blueprint $table) {
            if (! Schema::hasColumn('breed_females', 'exit_reason_code')) {
                $table->string('exit_reason_code', 80)->nullable()->after('exit_reason');
            }

            if (! Schema::hasColumn('breed_females', 'exit_notes')) {
                $table->text('exit_notes')->nullable()->after('exit_reason_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('breed_females', function (Blueprint $table) {
            if (Schema::hasColumn('breed_females', 'exit_notes')) {
                $table->dropColumn('exit_notes');
            }

            if (Schema::hasColumn('breed_females', 'exit_reason_code')) {
                $table->dropColumn('exit_reason_code');
            }
        });
    }
};
