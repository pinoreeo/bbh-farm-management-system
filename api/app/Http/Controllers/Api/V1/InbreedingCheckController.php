<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InbreedingRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InbreedingCheckController extends Controller
{
    public function __invoke(Request $request, InbreedingRiskService $inbreeding): JsonResponse
    {
        $data = $this->validated($request, [
            'sire_id' => ['required', 'integer', 'exists:animals,id'],
            'dam_id' => ['required', 'integer', 'exists:animals,id'],
        ]);

        return response()->json($inbreeding->evaluate((int) $data['sire_id'], (int) $data['dam_id']));
    }
}
