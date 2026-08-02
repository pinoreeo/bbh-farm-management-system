<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AnimalStoreRequest;
use App\Http\Requests\Api\V1\AnimalUpdateRequest;
use App\Models\Animal;
use App\Services\AnimalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function __construct(private readonly AnimalService $animals) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->animals->paginate($request, $this->perPage($request)));
    }

    public function store(AnimalStoreRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->animals->store($request, $request->validated()));
    }

    public function show(Animal $animal): JsonResponse
    {
        return response()->json($this->animals->loadDetail($animal));
    }

    public function update(AnimalUpdateRequest $request, Animal $animal): JsonResponse
    {
        return $this->serviceResponse($this->animals->update($request, $animal, $request->validated()));
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
