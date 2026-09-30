<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\BreedingFemale;
use App\Models\HealthTreatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['year' => ['nullable', 'integer']]);
        $requestedYear = $request->filled('year') ? $request->integer('year') : null;

        $birthChartsByYear = [];
        $offspringChartsByYear = [];
        $currentYearBirthChart = array_fill(0, 12, 0);
        foreach (BirthEvent::query()->select(['id', 'birth_date', 'offspring_count'])->lazyById(500) as $event) {
            if ($event->birth_date === null) {
                continue;
            }
            $eventYear = $event->birth_date->year;
            $month = $event->birth_date->month - 1;
            $birthChartsByYear[$eventYear] ??= array_fill(0, 12, 0);
            $offspringChartsByYear[$eventYear] ??= array_fill(0, 12, 0);
            $birthChartsByYear[$eventYear][$month]++;
            $offspringChartsByYear[$eventYear][$month] += max(1, (int) $event->offspring_count);
            if ($eventYear === (int) now()->year) {
                $currentYearBirthChart[$month]++;
            }
        }
        $birthYears = array_keys($birthChartsByYear);
        rsort($birthYears);
        $birthYears = $birthYears ?: [(int) now()->year];
        $year = in_array($requestedYear, $birthYears, true) ? $requestedYear : $birthYears[0];
        $birthChart = $birthChartsByYear[$year] ?? array_fill(0, 12, 0);
        $offspringChart = $offspringChartsByYear[$year] ?? array_fill(0, 12, 0);

        $groups = ['all', 'adultMales', 'adultFemales', 'youngMales', 'readyFemales', 'kids', 'pregnant'];
        $counts = array_fill_keys($groups, 0);
        $trends = array_fill_keys($groups, array_fill(0, 12, 0));
        $totalAnimals = 0;
        foreach (Animal::query()->select([
            'id', 'birth_date', 'sex', 'life_status', 'reproductive_status', 'status_date',
        ])->lazyById(500) as $animal) {
            $totalAnimals++;
            $isAlive = $animal->life_status !== 'dead';
            $category = $isAlive ? $animal->kategori_umur : null;
            $animalGroups = ['all'];
            if ($isAlive) {
                $counts['all']++;
                $group = match ($category) {
                    'pejantan dewasa' => 'adultMales', 'betina dewasa' => 'adultFemales',
                    'pejantan muda' => 'youngMales', 'dere' => 'readyFemales',
                    default => null,
                };
                if ($group !== null) {
                    $animalGroups[] = $group;
                    $counts[$group]++;
                }
                if ($animal->birth_date !== null && $animal->birth_date->diffInMonths(now()) <= 6) {
                    $animalGroups[] = 'kids';
                    $counts['kids']++;
                }
                if ($animal->reproductive_status === 'bunting') {
                    $animalGroups[] = 'pregnant';
                    $counts['pregnant']++;
                }
            }

            foreach ($animalGroups as $group) {
                $date = $group === 'pregnant' ? $animal->status_date : $animal->birth_date;
                if ($date !== null && $date->year === $year) {
                    $trends[$group][$date->month - 1]++;
                }
            }
        }

        $today = now()->toDateString();
        $inFortyFiveDays = now()->addDays(45)->toDateString();
        $femaleRelations = ['femaleAnimal:id,tag_number,reproductive_status', 'breedingPeriod:id,period_code'];
        $unmatedFemales = BreedingFemale::query()->with($femaleRelations)->whereNull('exit_date')
            ->whereNull('mating_date')->orderBy('entry_date')->limit(12)->get();
        $overdueFemales = BreedingFemale::query()->with($femaleRelations)->whereNull('exit_date')
            ->whereDate('expected_birth_date', '<', $today)
            ->orderByDesc('expected_birth_date')->limit(12)->get();
        $upcomingFemales = BreedingFemale::query()->with($femaleRelations)->whereNull('exit_date')
            ->whereBetween('expected_birth_date', [$today, $inFortyFiveDays])
            ->orderBy('expected_birth_date')->limit(12)->get();
        $breedingFemales = $unmatedFemales->concat($overdueFemales)->concat($upcomingFemales)
            ->unique('id')->values()->toArray();

        $healthRelations = ['animal:id,tag_number'];
        $overdueHealth = HealthTreatment::query()->with($healthRelations)->whereNotNull('next_control_date')
            ->whereDate('next_control_date', '<', $today)->orderByDesc('next_control_date')->limit(12)->get();
        $upcomingHealth = HealthTreatment::query()->with($healthRelations)
            ->whereBetween('next_control_date', [$today, $inFortyFiveDays])
            ->orderBy('next_control_date')->limit(12)->get();
        $healthTreatments = $overdueHealth->concat($upcomingHealth)->unique('id')->values()->toArray();

        return response()->json([
            'total_animals' => $totalAnimals,
            'counts' => $counts,
            'trends' => $trends,
            'birth_years' => $birthYears,
            'selected_birth_year' => $year,
            'birth_chart' => $birthChart,
            'offspring_chart' => $offspringChart,
            'current_year_birth_chart' => $currentYearBirthChart,
            'breeding_females' => $breedingFemales,
            'health_treatments' => $healthTreatments,
            'activities' => $request->user()?->role === 'super_admin'
                ? AdminActivityLog::query()->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get()
                : [],
            'latest_animals' => Animal::query()->with('breed:id,breed_name')->orderByDesc('id')->limit(5)->get(),
        ]);
    }
}
