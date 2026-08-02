<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OffspringBirth;
use App\Services\OffspringBirthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OffspringBirthController extends Controller
{
    public function __construct(private readonly OffspringBirthService $offspringBirths) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->offspringBirths->paginate($request, $this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'birth_event_id' => ['required', 'integer', 'exists:breed_births,id'],
            'offspring_animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'tag_number' => ['nullable', 'string', 'max:100', 'unique:animals,tag_number'],
            'breed_id' => ['required_without:offspring_animal_id', 'integer', 'exists:animal_breeds,id'],
            'sex' => ['required_without:offspring_animal_id', 'in:male,female'],
            'generation' => ['required_without:offspring_animal_id', Rule::in(['F1', 'F2', 'F3', 'F4', 'F5', 'Pure Breed'])],
            'birth_weight_kg' => ['required', 'numeric', 'min:0'],
            'offspring_grade' => ['nullable', 'string', 'max:50'],
            'birth_status' => ['nullable', 'in:alive,dead'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->offspringBirths->store($data));
    }

    public function show(OffspringBirth $offspringBirth): JsonResponse
    {
        return response()->json($this->offspringBirths->loadSummary($offspringBirth));
    }

    public function update(Request $request, OffspringBirth $offspringBirth): JsonResponse
    {
        $data = $this->validated($request, [
            'birth_weight_kg' => ['sometimes', 'numeric', 'min:0'],
            'offspring_grade' => ['nullable', 'string', 'max:50'],
            'birth_status' => ['sometimes', 'in:alive,dead'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->offspringBirths->update($offspringBirth, $data));
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
