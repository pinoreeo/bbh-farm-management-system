<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PregnancyCheck;
use App\Services\PregnancyCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PregnancyCheckController extends Controller
{
    public function __construct(private readonly PregnancyCheckService $pregnancyChecks) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->pregnancyChecks->paginate($request, $this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'breeding_female_id' => ['nullable', 'integer', 'exists:breed_females,id'],
            'breeding_period_id' => ['required_without:breeding_female_id', 'integer', 'exists:breed_periods,id'],
            'female_animal_id' => ['required_without:breeding_female_id', 'integer', 'exists:animals,id'],
            'check_date' => ['required', 'date'],
            'is_pregnant' => ['required', 'boolean'],
            'outcome_status' => ['nullable', 'in:born'],
            'method' => ['nullable', 'string', 'max:100'],
            'estimated_gestation_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->pregnancyChecks->store($data));
    }

    public function show(PregnancyCheck $pregnancyCheck): JsonResponse
    {
        return response()->json($this->pregnancyChecks->loadRelations($pregnancyCheck));
    }

    public function update(Request $request, PregnancyCheck $pregnancyCheck): JsonResponse
    {
        $data = $this->validated($request, [
            'breeding_female_id' => ['sometimes', 'integer', 'exists:breed_females,id'],
            'breeding_period_id' => ['sometimes', 'integer', 'exists:breed_periods,id'],
            'female_animal_id' => ['sometimes', 'integer', 'exists:animals,id'],
            'check_date' => ['sometimes', 'date'],
            'is_pregnant' => ['sometimes', 'boolean'],
            'outcome_status' => ['nullable', 'in:born'],
            'method' => ['nullable', 'string', 'max:100'],
            'estimated_gestation_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->pregnancyChecks->update($pregnancyCheck, $data));
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
