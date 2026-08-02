<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breed_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('breed_periods', 'mating_date')) {
                $table->dropColumn('mating_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('breed_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('breed_periods', 'mating_date')) {
                $table->date('mating_date')->nullable()->after('start_date');
            }
        });
    }
};
