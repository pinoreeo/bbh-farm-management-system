<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breed_periods', function (Blueprint $table) {
            if (Schema::hasColumn('breed_periods', 'expected_birth_date')) {
                $table->dropColumn('expected_birth_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('breed_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('breed_periods', 'expected_birth_date')) {
                $table->date('expected_birth_date')->nullable()->after('mating_date');
            }
        });
    }
};
