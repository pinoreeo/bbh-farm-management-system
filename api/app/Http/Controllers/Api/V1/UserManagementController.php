<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $q = User::query();

        if ($request->filled('role')) {
            $q->where('role', $request->query('role'));
        }

        if ($request->filled('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($q->orderBy('name')->paginate($this->perPage($request)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validated();
        $data = $this->normalizeNamePayload($data);
        $data['name'] = $this->fullName($data['first_name'], $data['last_name'] ?? null);
        $data['password'] = Hash::make((string) $data['password']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['email_verified_at'] = now();

        $user = User::query()->create($data);

        return response()->json([
            'message' => 'Sukses: Akun pengguna berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validated();
        if (array_key_exists('name', $data) && ! array_key_exists('first_name', $data)) {
            $data = $this->normalizeNamePayload($data);
        }

        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data) || array_key_exists('name', $data)) {
            $data['name'] = $this->fullName(
                $data['first_name'] ?? $user->first_name ?? $user->name,
                $data['last_name'] ?? $user->last_name
            );
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make((string) $data['password']);
            $user->tokens()->delete();
        } else {
            unset($data['password']);
        }

        if (array_key_exists('is_active', $data) && ! $data['is_active']) {
            $activeSuperAdmins = User::query()
                ->where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($user->role === 'super_admin' && $activeSuperAdmins < 1) {
                return response()->json([
                    'message' => 'Peringatan: Minimal harus ada satu super admin aktif.',
                ], 422);
            }

            $user->tokens()->delete();
        }

        $user->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Akun pengguna berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless(($request->user()?->role ?? null) === 'super_admin', 403, 'Hanya super admin yang dapat mengelola pengguna.');
    }

    private function fullName(string $firstName, ?string $lastName): string
    {
        return trim($firstName.' '.($lastName ?? ''));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeNamePayload(array $data): array
    {
        if (! empty($data['first_name'])) {
            return $data;
        }

        $parts = preg_split('/\s+/', trim((string) ($data['name'] ?? '')), 2) ?: [];
        $data['first_name'] = $parts[0] ?? '';
        $data['last_name'] = $data['last_name'] ?? ($parts[1] ?? null);

        return $data;
    }
}
