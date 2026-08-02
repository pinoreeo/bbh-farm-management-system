<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('breed_periods')
            ->select(['id', 'start_date', 'mating_date'])
            ->orderBy('id')
            ->get()
            ->each(function ($period): void {
                $matingDate = $period->mating_date ?? $period->start_date;

                if (! $matingDate) {
                    return;
                }

                DB::table('breed_periods')
                    ->where('id', $period->id)
                    ->update([
                        'mating_date' => $matingDate,
                        'expected_birth_date' => $this->expectedBirthDate($matingDate),
                    ]);
            });

        DB::table('breed_females')
            ->leftJoin('breed_periods', 'breed_females.breeding_period_id', '=', 'breed_periods.id')
            ->select([
                'breed_females.id',
                'breed_females.entry_date',
                'breed_females.mating_date',
                'breed_periods.mating_date as period_mating_date',
            ])
            ->orderBy('breed_females.id')
            ->get()
            ->each(function ($female): void {
                $matingDate = $female->mating_date ?? $female->period_mating_date ?? $female->entry_date;

                if (! $matingDate) {
                    return;
                }

                DB::table('breed_females')
                    ->where('id', $female->id)
                    ->update([
                        'mating_date' => $matingDate,
                        'expected_birth_date' => $this->expectedBirthDate($matingDate),
                    ]);
            });
    }

    public function down(): void
    {
        // Data correction only. The original values cannot be reconstructed safely.
    }

    private function expectedBirthDate(string $matingDate): string
    {
        return Carbon::parse($matingDate)->addMonthsNoOverflow(5)->addDays(10)->toDateString();
    }
};
