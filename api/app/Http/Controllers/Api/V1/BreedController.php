<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Breed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BreedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = Breed::query();

        if ($request->filled('is_active')) {
            $q->where('is_active', (bool) $request->query('is_active'));
        } elseif (! (int) $request->query('include_inactive', 0)) {
            $q->where('is_active', true);
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $q->where('breed_name', 'like', "%{$s}%");
        }

        return response()->json(
            $q->orderBy('breed_name')
                ->orderBy('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'breed_name' => ['required', 'string', 'max:150', 'unique:animal_breeds,breed_name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $row = Breed::create($data);

        return response()->json([
            'message' => 'Sukses: Data ras kambing berhasil disimpan.',
            'data' => $row,
        ], 201);
    }

    public function show(Breed $breed): JsonResponse
    {
        return response()->json($breed);
    }

    public function update(Request $request, Breed $breed): JsonResponse
    {
        $data = $this->validated($request, [
            'breed_name' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('animal_breeds', 'breed_name')->ignore($breed->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $breed->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Data ras kambing berhasil diperbarui.',
            'data' => $breed,
        ]);
    }
}
