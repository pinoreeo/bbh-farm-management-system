<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BreedingPeriodStoreRequest;
use App\Http\Requests\Api\V1\BreedingPeriodUpdateRequest;
use App\Models\BreedingPeriod;
use App\Services\BreedingPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BreedingPeriodController extends Controller
{
    public function __construct(private readonly BreedingPeriodService $breedingPeriods) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->breedingPeriods->paginate($request, $this->perPage($request)));
    }

    public function store(BreedingPeriodStoreRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->breedingPeriods->store($request->validated()));
    }

    public function show(BreedingPeriod $breedingPeriod): JsonResponse
    {
        return response()->json($this->breedingPeriods->loadDetail($breedingPeriod));
    }

    public function update(BreedingPeriodUpdateRequest $request, BreedingPeriod $breedingPeriod): JsonResponse
    {
        return $this->serviceResponse($this->breedingPeriods->update($breedingPeriod, $request->validated(), array_keys($request->all())));
    }

    public function close(BreedingPeriod $breedingPeriod): JsonResponse
    {
        return $this->serviceResponse($this->breedingPeriods->close($breedingPeriod));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function serviceResponse(array $result): JsonResponse
    {
        $payload = ['message' => $result['message']];

        if (array_key_exists('data', $result)) {
            $payload['data'] = $result['data'];
        }

        return response()->json($payload, $result['status'] ?? ($result['ok'] ? 200 : 422));
    }
}
