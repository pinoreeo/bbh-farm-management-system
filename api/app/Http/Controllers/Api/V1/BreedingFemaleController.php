<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BreedingFemale\ExitBreedingFemaleRequest;
use App\Http\Requests\Api\V1\BreedingFemale\RecordBreedingFemaleMatingRequest;
use App\Http\Requests\Api\V1\BreedingFemale\StoreBreedingFemaleRequest;
use App\Http\Requests\Api\V1\BreedingFemale\UpdateBreedingFemaleRequest;
use App\Models\BreedingFemale;
use App\Services\BreedingFemaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BreedingFemaleController extends Controller
{
    public function __construct(private readonly BreedingFemaleService $breedingFemales) {}

    public function index(Request $request): JsonResponse
    {
        $query = BreedingFemale::query()->with([
            'breedingPeriod:id,period_code,colony_pen_id,male_animal_id,start_date,end_date,status',
            'femaleAnimal:id,tag_number,sex,reproductive_status,current_pen_id,life_status,birth_date',
        ]);

        if ($request->filled('breeding_period_id')) {
            $query->where('breeding_period_id', (int) $request->query('breeding_period_id'));
        }

        if ($request->filled('female_animal_id')) {
            $query->where('female_animal_id', (int) $request->query('female_animal_id'));
        }

        return response()->json($query->orderByDesc('id')->paginate($this->perPage($request)));
    }

    public function store(StoreBreedingFemaleRequest $request): JsonResponse
    {
        if ($this->breedingFemales->hasExitPayload($request)) {
            return $this->exitOnlyResponse();
        }

        return $this->serviceResponse($this->breedingFemales->store($request->validated()));
    }

    public function show(BreedingFemale $breedingFemale): JsonResponse
    {
        return response()->json($breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']));
    }

    public function update(UpdateBreedingFemaleRequest $request, BreedingFemale $breedingFemale): JsonResponse
    {
        if ($this->breedingFemales->hasExitPayload($request)) {
            return $this->exitOnlyResponse();
        }

        if ($this->breedingFemales->hasMatingPayload($request)) {
            return response()->json([
                'message' => 'Peringatan: Tanggal kawin hanya dapat dicatat melalui aksi Catat Kawin.',
            ], 422);
        }

        return $this->serviceResponse($this->breedingFemales->update($breedingFemale, $request->validated()));
    }

    public function recordMating(RecordBreedingFemaleMatingRequest $request, BreedingFemale $breedingFemale): JsonResponse
    {
        return $this->serviceResponse($this->breedingFemales->recordMating($breedingFemale, $request->validated('mating_date')));
    }

    public function exit(ExitBreedingFemaleRequest $request, BreedingFemale $breedingFemale): JsonResponse
    {
        return $this->serviceResponse($this->breedingFemales->exit($breedingFemale, $request->validated()));
    }

    private function exitOnlyResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Peringatan: Keluar dari periode kawin hanya dapat diproses melalui aksi Keluarkan Betina.',
        ], 422);
    }

    private function serviceResponse(array $result): JsonResponse
    {
        if (($result['ok'] ?? false) !== true) {
            $payload = ['message' => $result['message']];
            if (isset($result['inbreeding'])) {
                $payload['inbreeding'] = $result['inbreeding'];
            }

            return response()->json($payload, $result['status'] ?? 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status'] ?? 200);
    }
}
