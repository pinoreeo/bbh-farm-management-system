<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BirthEvent;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use App\Models\HealthTreatment;
use App\Models\OffspringBirth;
use App\Models\PregnancyCheck;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportExportDataService
{
    public function data(string $report, Request $request): array
    {
        return match ($report) {
            'animals' => [
                'Data Kambing',
                ['Eartag', 'Ras', 'Generasi', 'Jenis Kelamin', 'Tanggal Lahir', 'Status Hidup', 'Status Ternak', 'Tanggal Status', 'Asal'],
                Animal::query()->with(['breed', 'currentPen'])->orderBy('tag_number')->get()->map(fn (Animal $item) => [
                    $item->tag_number,
                    $item->breed?->breed_name,
                    $item->generation,
                    $this->sex($item->sex),
                    $item->birth_date?->toDateString(),
                    $this->lifeStatus($item->life_status),
                    $this->animalStatus($item->exit_status),
                    $item->status_date?->toDateString(),
                    $item->is_impor ? 'Impor' : 'Lokal',
                ])->all(),
            ],
            'deaths' => [
                'Data Kematian',
                ['Eartag', 'Ras', 'Jenis Kelamin', 'Tanggal Lahir', 'Tanggal Status', 'Status Hidup', 'Catatan'],
                $this->dateRange(Animal::query()->with('breed')->where('life_status', 'dead'), $request, 'status_date')
                    ->orderByDesc('status_date')
                    ->get()
                    ->map(fn (Animal $item) => [
                        $item->tag_number,
                        $item->breed?->breed_name,
                        $this->sex($item->sex),
                        $item->birth_date?->toDateString(),
                        $item->status_date?->toDateString(),
                        $this->lifeStatus($item->life_status),
                        $item->notes,
                    ])->all(),
            ],
            'pens' => [
                'Data Kandang Koloni',
                ['Kode Kandang', 'Kode Koloni', 'Nama Koloni', 'Fase Koloni', 'Lokasi', 'Kapasitas', 'Jumlah Isi', 'Status'],
                ColonyPen::query()->withCount('animals')->orderBy('pen_code')->get()->map(fn (ColonyPen $item) => [
                    $item->pen_code,
                    $item->colony_code,
                    $item->colony_name,
                    $this->colonyPhase($item->colony_phase),
                    $item->location,
                    $item->capacity,
                    $item->animals_count,
                    $item->is_active ? 'Aktif' : 'Nonaktif',
                ])->all(),
            ],
            'births' => [
                'Data Kelahiran',
                ['Tanggal Lahir', 'Jam', 'Induk', 'Pejantan', 'Jumlah Anak', 'Proses', 'Tempat', 'Catatan'],
                $this->dateRange(BirthEvent::query()->with(['dam', 'sire']), $request, 'birth_date')->orderByDesc('birth_date')->get()->map(fn (BirthEvent $item) => [
                    $item->birth_date?->toDateString(),
                    $item->birth_time,
                    $item->dam?->tag_number,
                    $item->sire?->tag_number,
                    $item->offspring_count,
                    $item->birth_process,
                    $item->birth_place,
                    $item->notes,
                ])->all(),
            ],
            'offsprings' => [
                'Data Anak',
                ['Tanggal Lahir', 'Eartag Anak', 'Induk', 'Pejantan', 'Berat Lahir', 'Grade Anak', 'Status Anak', 'Catatan'],
                OffspringBirth::query()->with(['offspringAnimal', 'birthEvent.dam', 'birthEvent.sire'])->orderByDesc('id')->get()->map(fn (OffspringBirth $item) => [
                    $item->birthEvent?->birth_date?->toDateString(),
                    $item->offspringAnimal?->tag_number,
                    $item->birthEvent?->dam?->tag_number,
                    $item->birthEvent?->sire?->tag_number,
                    $item->birth_weight_kg,
                    $item->offspring_grade,
                    $this->lifeStatus($item->birth_status),
                    $item->notes,
                ])->all(),
            ],
            'weights' => [
                'Data Bobot',
                ['Eartag', 'Tanggal Timbang', 'Berat Kg', 'Catatan'],
                $this->dateRange(WeightRecord::query()->with('animal'), $request, 'record_date')->orderByDesc('record_date')->get()->map(fn (WeightRecord $item) => [
                    $item->animal?->tag_number,
                    $item->record_date?->toDateString(),
                    $item->weight_kg,
                    $item->notes,
                ])->all(),
            ],
            'health' => [
                'Data Kesehatan',
                ['Eartag', 'Tanggal', 'Jenis Perawatan', 'Gejala', 'Diagnosis', 'Produk/Obat', 'Dosis', 'Rute', 'Tindakan', 'Ditangani Oleh', 'Kontrol Berikutnya', 'Catatan'],
                $this->dateRange(HealthTreatment::query()->with('animal'), $request, 'treatment_date')->orderByDesc('treatment_date')->get()->map(fn (HealthTreatment $item) => [
                    $item->animal?->tag_number,
                    $item->treatment_date?->toDateString(),
                    $item->treatment_group,
                    $item->symptoms,
                    $item->diagnosis,
                    $item->product_name,
                    $item->dosage,
                    $item->administration_route,
                    $item->action_category,
                    $item->handled_by,
                    $item->next_control_date?->toDateString(),
                    $item->notes,
                ])->all(),
            ],
            'vaccinations' => [
                'Data Vaksinasi',
                ['Eartag', 'Jenis Vaksin', 'Tanggal Vaksin', 'Nama Vaksin', 'Dosis', 'Rute', 'Catatan'],
                $this->dateRange(Vaccination::query()->with('animal'), $request, 'vaccination_date')->orderByDesc('vaccination_date')->get()->map(fn (Vaccination $item) => [
                    $item->animal?->tag_number,
                    $item->category_name,
                    $item->vaccination_date?->toDateString(),
                    $item->product_name,
                    $item->dosage,
                    $item->administration_route,
                    $item->notes,
                ])->all(),
            ],
            'breeding' => [
                'Data Perkawinan',
                ['Kode Periode', 'Koloni', 'Tanggal Mulai', 'Tanggal Selesai', 'Pejantan', 'Status'],
                $this->dateRange(BreedingPeriod::query()->with(['colonyPen', 'maleAnimal']), $request, 'start_date')->orderByDesc('start_date')->get()->map(fn (BreedingPeriod $item) => [
                    $item->period_code,
                    $item->colonyPen?->pen_code,
                    $item->start_date?->toDateString(),
                    $item->end_date?->toDateString(),
                    $item->maleAnimal?->tag_number,
                    $item->status,
                ])->all(),
            ],
            'breeding-females' => [
                'Data Betina Kawin',
                ['Kode Periode', 'Eartag Betina', 'Tanggal Masuk', 'Tanggal Kawin', 'Perkiraan Lahir', 'Tahap Siklus', 'Tanggal Keluar', 'Alasan Keluar', 'Catatan Keluar'],
                $this->dateRange(BreedingFemale::query()->with(['breedingPeriod', 'femaleAnimal']), $request, 'entry_date')->orderByDesc('entry_date')->get()->map(fn (BreedingFemale $item) => [
                    $item->breedingPeriod?->period_code,
                    $item->femaleAnimal?->tag_number,
                    $item->entry_date?->toDateString(),
                    $item->mating_date?->toDateString(),
                    $item->expected_birth_date?->toDateString(),
                    $this->reproductiveStatus($item->cycle_stage),
                    $item->exit_date?->toDateString(),
                    $item->exit_reason,
                    $item->exit_notes,
                ])->all(),
            ],
            'pregnancies' => [
                'Data Kebuntingan',
                ['Kode Periode', 'Eartag Betina', 'Tanggal Kawin', 'Perkiraan Lahir', 'Tanggal Periksa', 'Status', 'Metode', 'Estimasi Hari', 'Outcome', 'Catatan'],
                $this->dateRange(PregnancyCheck::query()->with(['breedingPeriod', 'breedingFemale', 'femaleAnimal']), $request, 'check_date')->orderByDesc('check_date')->get()->map(fn (PregnancyCheck $item) => [
                    $item->breedingPeriod?->period_code,
                    $item->femaleAnimal?->tag_number,
                    $item->breedingFemale?->mating_date?->toDateString(),
                    $item->breedingFemale?->expected_birth_date?->toDateString(),
                    $item->check_date?->toDateString(),
                    $item->is_pregnant ? 'Bunting' : 'Tidak Bunting',
                    $item->method,
                    $item->estimated_gestation_days,
                    $item->outcome_status,
                    $item->notes,
                ])->all(),
            ],
            'pen-movements' => [
                'Riwayat Pindah Koloni',
                ['Eartag', 'Dari Koloni', 'Ke Koloni', 'Tanggal Keluar/Masuk Koloni', 'Alasan', 'Catatan'],
                $this->dateRange(AnimalPenMovement::query()->with(['animal', 'fromPen', 'toPen']), $request, 'movement_date')->orderByDesc('movement_date')->get()->map(fn (AnimalPenMovement $item) => [
                    $item->animal?->tag_number,
                    $item->fromPen?->pen_code,
                    $item->toPen?->pen_code,
                    $item->movement_date?->toDateString(),
                    $item->reason,
                    $item->notes,
                ])->all(),
            ],
            'activity-logs' => [
                'Log Aktivitas',
                ['Tanggal', 'Waktu', 'Admin', 'Email', 'Modul', 'Aksi', 'Detail', 'Status HTTP', 'IP Address'],
                $this->dateRange(AdminActivityLog::query()->with('admin:id,name,email'), $request, 'created_at')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn (AdminActivityLog $item) => [
                        $item->created_at?->toDateString(),
                        $item->created_at?->format('H:i'),
                        $item->admin_name ?: $item->admin?->name,
                        $item->admin_email ?: $item->admin?->email,
                        $this->activityModule($item->module),
                        $this->activityAction($item->action),
                        $item->description,
                        $item->status_code,
                        $item->ip_address,
                    ])->all(),
            ],
            default => abort(404),
        };
    }

    private function dateRange(Builder $query, Request $request, string $column): Builder
    {
        if ($request->filled('year')) {
            $query->whereYear($column, (int) $request->query('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth($column, (int) $request->query('month'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->query('date_to'));
        }

        return $query;
    }

    private function sex(?string $value): string
    {
        return match ($value) {
            'male' => 'Jantan',
            'female' => 'Betina',
            default => '-',
        };
    }

    private function lifeStatus(?string $value): string
    {
        return match ($value) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => '-',
        };
    }

    private function reproductiveStatus(?string $value): string
    {
        return match ($value) {
            'kosong' => 'Kosong',
            'kawin' => 'Kawin',
            'bunting' => 'Bunting',
            'kering' => 'Kering',
            'laktasi_kosong' => 'Laktasi Kosong',
            'melahirkan' => 'Melahirkan',
            'afkir' => 'Afkir',
            default => $value ?: '-',
        };
    }

    private function colonyPhase(?string $value): string
    {
        return match ($value) {
            'koloni_kawin' => 'Koloni Kawin',
            'koloni_bunting' => 'Koloni Bunting',
            'koloni_kering' => 'Koloni Kering',
            'koloni_laktasi', 'koloni_laktasi_kosong' => 'Koloni Laktasi',
            'koloni_anak' => 'Koloni Anak/Cempe',
            default => $value ?: '-',
        };
    }

    private function animalStatus(?string $value): string
    {
        return match ($value) {
            'sold' => 'Dijual',
            'culled' => 'Afkir / Tidak Produktif',
            'lost' => 'Hilang',
            default => '-',
        };
    }

    private function activityAction(?string $value): string
    {
        return match ($value) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create' => 'Tambah Data',
            'update' => 'Perbarui Data',
            'delete' => 'Nonaktifkan Data',
            'sign' => 'Tanda Tangan Sertifikat',
            'revoke' => 'Cabut Sertifikat',
            'unrevoke' => 'Aktifkan Kembali Sertifikat',
            'generate' => 'Generate RSA Key',
            'activate' => 'Aktivasi',
            'deactivate' => 'Nonaktifkan',
            'compromise' => 'Nonaktifkan RSA Key',
            default => $value ?: '-',
        };
    }

    private function activityModule(?string $value): string
    {
        return match ($value) {
            'auth' => 'Autentikasi',
            'farm' => 'Profil Farm',
            'breeds' => 'Ras Kambing',
            'animals' => 'Data Kambing',
            'colony-pens' => 'Kandang & Koloni',
            'breeding-periods' => 'Periode Kawin',
            'breeding-females' => 'Betina Kawin',
            'pregnancy-checks' => 'Kebuntingan',
            'birth-events' => 'Kelahiran',
            'offspring-births' => 'Cempe Lahir',
            'postnatal-care-records' => 'Pascalahir',
            'weight-records' => 'Catatan Bobot',
            'health-treatments' => 'Kesehatan',
            'vaccinations' => 'Vaksinasi',
            'certificates' => 'Akte & Sertifikat',
            'rsa-keys' => 'RSA Key',
            default => $value ?: '-',
        };
    }
}
