<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BirthEvent;
use App\Services\BirthEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BirthEventController extends Controller
{
    public function __construct(private readonly BirthEventService $birthEvents) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->birthEvents->paginate($request, $this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'dam_id' => ['required', 'integer', 'exists:animals,id'],
            'sire_id' => ['nullable', 'integer', 'exists:animals,id'],
            'birth_date' => ['required', 'date'],
            'birth_time' => ['nullable', 'date_format:H:i:s'],
            'offspring_count' => ['required', 'integer', 'min:1'],
            'birth_process' => ['required', 'string', 'max:100'],
            'dam_grade' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->birthEvents->store($data));
    }

    public function show(BirthEvent $birthEvent): JsonResponse
    {
        return response()->json($this->birthEvents->loadDetail($birthEvent));
    }

    public function update(Request $request, BirthEvent $birthEvent): JsonResponse
    {
        $data = $this->validated($request, [
            'dam_id' => ['sometimes', 'integer', 'exists:animals,id'],
            'sire_id' => ['nullable', 'integer', 'exists:animals,id'],
            'birth_date' => ['sometimes', 'date'],
            'birth_time' => ['nullable', 'date_format:H:i:s'],
            'offspring_count' => ['sometimes', 'integer', 'min:1'],
            'birth_process' => ['sometimes', 'string', 'max:100'],
            'dam_grade' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->serviceResponse($this->birthEvents->update($birthEvent, $data));
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
