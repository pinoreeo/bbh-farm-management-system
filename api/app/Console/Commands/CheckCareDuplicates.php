<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckCareDuplicates extends Command
{
    protected $signature = 'bbh:check-care-duplicates';

    protected $description = 'Periksa duplikat catatan perawatan dan vaksinasi sebelum memasang indeks unik';

    public function handle(): int
    {
        $checks = [
            'med_treatments' => ['animal_id', 'treatment_group', 'product_name', 'treatment_date'],
            'med_vaccinations' => ['animal_id', 'category_name', 'vaccination_date', 'product_name'],
        ];
        $hasDuplicates = false;

        foreach ($checks as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $this->warn("Tabel {$table} belum tersedia.");

                continue;
            }

            $duplicates = DB::table($table)
                ->select($columns)
                ->selectRaw('COUNT(*) as record_count')
                ->groupBy($columns)
                ->havingRaw('COUNT(*) > 1')
                ->limit(10)
                ->get();

            if ($duplicates->isEmpty()) {
                $this->info("{$table}: tidak ada duplikat.");

                continue;
            }

            $hasDuplicates = true;
            $this->error("{$table}: ditemukan kelompok catatan duplikat. Periksa sebelum migrasi.");
            $this->table([...$columns, 'record_count'], $duplicates->map(fn ($row) => array_values((array) $row))->all());
        }

        return $hasDuplicates ? self::FAILURE : self::SUCCESS;
    }
}
