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

        return response()->json($inbreeding->evaluate(
            $this->intValue($data['sire_id'] ?? null),
            $this->intValue($data['dam_id'] ?? null),
        ));
    }
}
