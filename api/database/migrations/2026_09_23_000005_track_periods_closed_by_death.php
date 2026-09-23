<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breed_periods', function (Blueprint $table): void {
            $table->boolean('closed_by_male_death')->default(false);
        });

        $periodIds = DB::table('breed_periods as period')
            ->join('animals as male', 'male.id', '=', 'period.male_animal_id')
            ->join('breed_females as female', 'female.breeding_period_id', '=', 'period.id')
            ->where('period.status', 'closed')
            ->where('male.life_status', 'dead')
            ->where('female.exit_reason_code', 'pejantan_mati')
            ->whereColumn('male.status_date', 'period.end_date')
            ->whereColumn('female.exit_date', 'period.end_date')
            ->distinct()
            ->pluck('period.id');

        DB::table('breed_periods')->whereIn('id', $periodIds)->update(['closed_by_male_death' => true]);
    }

    public function down(): void
    {
        Schema::table('breed_periods', function (Blueprint $table): void {
            $table->dropColumn('closed_by_male_death');
        });
    }
};
