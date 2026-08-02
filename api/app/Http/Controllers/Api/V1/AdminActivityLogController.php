<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = AdminActivityLog::query()->with('admin:id,name,email');

        if ($request->filled('module')) {
            $q->where('module', $request->query('module'));
        }

        if ($request->filled('action')) {
            $q->where('action', $request->query('action'));
        }

        if ($request->filled('admin_id')) {
            $q->where('admin_id', (int) $request->query('admin_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $q->where(function ($query) use ($search) {
                $query
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('admin_name', 'like', "%{$search}%")
                    ->orWhere('admin_email', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        return response()->json($q->orderByDesc('created_at')->paginate($perPage));
    }

    public function show(AdminActivityLog $adminActivityLog): JsonResponse
    {
        return response()->json($adminActivityLog->load('admin:id,name,email'));
    }
}
