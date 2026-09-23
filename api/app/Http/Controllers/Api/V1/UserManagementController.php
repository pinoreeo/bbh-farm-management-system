<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Models\User;
use App\Services\AdminInvitationService;
use App\Services\AuthService;
use App\Support\TypeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $q = User::query();

        if ($request->filled('role')) {
            $q->where('role', TypeValue::string($request->query('role')));
        }

        if ($request->filled('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = trim(TypeValue::string($request->query('search')));
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($q->orderBy('name')->paginate($this->perPage($request)));
    }

    public function store(StoreUserRequest $request, AdminInvitationService $invitations): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $data = TypeValue::stringKeyArray($request->validated());
        $data = $this->normalizeNamePayload($data);
        $data['name'] = $this->fullName(TypeValue::string($data['first_name'] ?? ''), TypeValue::nullableString($data['last_name'] ?? null));
        $user = DB::transaction(function () use ($data, $invitations): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => TypeValue::nullableString($data['phone'] ?? null),
                'password' => Hash::make(Str::random(48)),
                'role' => 'admin',
                'is_active' => false,
            ]);

            $invitations->send($user);

            return $user;
        });

        return response()->json([
            'message' => 'Sukses: Undangan untuk membuat password telah dikirim ke email admin.',
            'data' => $user,
        ], 201);
    }

    public function completeRegistration(Request $request): JsonResponse
    {
        abort(410, 'Alur verifikasi SMS sudah tidak digunakan.');
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $data = TypeValue::stringKeyArray($request->validated());

        return DB::transaction(function () use ($user, $data) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                User::query()->where('role', 'super_admin')->where('is_active', true)
                    ->orderBy('id')->lockForUpdate()->get();
            }
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (array_key_exists('name', $data) && ! array_key_exists('first_name', $data)) {
                $data = $this->normalizeNamePayload($data);
            }

            if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data) || array_key_exists('name', $data)) {
                $data['name'] = $this->fullName(
                    TypeValue::nullableString($data['first_name'] ?? null) ?? TypeValue::nullableString($user->first_name) ?? TypeValue::string($user->name),
                    TypeValue::nullableString($data['last_name'] ?? null) ?? TypeValue::nullableString($user->last_name)
                );
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
        }, 3);
    }

    public function sendPasswordResetLink(Request $request, User $user, AuthService $auth): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Peringatan: Tautan reset hanya dapat dikirim ke akun admin.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Peringatan: Aktifkan akun admin terlebih dahulu sebelum mengirim tautan reset password.',
            ], 422);
        }

        $auth->forgotPassword(['email' => $user->email]);

        return response()->json([
            'message' => 'Sukses: Tautan reset password telah dikirim ke email admin.',
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

        $parts = preg_split('/\s+/', trim(TypeValue::nullableString($data['name'] ?? null) ?? ''), 2) ?: [];
        $data['first_name'] = $parts[0] ?? '';
        $data['last_name'] = $data['last_name'] ?? ($parts[1] ?? null);

        return $data;
    }
}
