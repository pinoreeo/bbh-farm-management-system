<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateRevocation;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfIntegrityService;
use App\Services\CertificatePrintService;
use App\Services\CertificateSigningService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);

        $q = Certificate::query()->with([
            'animal.breed',
            'certificateType',
            'birthEvent',
            'signature.rsaKey',
            'revocation',
        ]);

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        } else {
            if (! (int) $request->query('include_inactive', 0)) {
                $q->where('status', 'active');
            }
        }

        if ($request->filled('certificate_type_id')) {
            $q->where('certificate_type_id', (int) $request->query('certificate_type_id'));
        }

        if ($request->filled('animal_id')) {
            $q->where('animal_id', (int) $request->query('animal_id'));
        }

        if ($request->filled('certificate_number')) {
            $q->where('certificate_number', $request->query('certificate_number'));
        }

        return response()->json(
            $q->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(Request $request, CertificateIssuanceService $certificates): JsonResponse
    {
        $data = $this->validated($request, [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'certificate_type_id' => ['required', 'integer', 'exists:cert_types,id'],
            'issue_place' => ['nullable', 'string', 'max:255'],
            'auto_sign' => ['nullable', 'boolean'],
            'death_date' => ['nullable', 'date'],
            'death_time' => ['nullable', 'date_format:H:i:s'],
            'cause_of_death' => ['nullable', 'string', 'max:255'],
        ]);

        $autoSign = array_key_exists('auto_sign', $data) ? (bool) $data['auto_sign'] : true;
        unset($data['auto_sign']);

        return $this->serviceResponse($certificates->issue($data, $autoSign));
    }

    public function show(Certificate $certificate): JsonResponse
    {
        return response()->json(
            $certificate->load([
                'animal.breed',
                'certificateType',
                'birthEvent',
                'signature.rsaKey',
                'revocation',
                'verificationLogs',
            ])
        );
    }

    public function update(Certificate $certificate): JsonResponse
    {
        return response()->json([
            'message' => 'Peringatan: Sertifikat yang sudah diterbitkan tidak dapat diedit. Cabut sertifikat lama dan terbitkan sertifikat baru jika data perlu diperbaiki.',
        ], 422);
    }

    public function revoke(Request $request, Certificate $certificate): JsonResponse
    {
        $data = $this->validated($request, [
            'reason' => ['required', 'string'],
            'revoked_at' => ['nullable', 'date'],
        ]);

        if (($certificate->status ?? 'active') === 'revoked') {
            return response()->json(['message' => 'Peringatan: Sertifikat ini sudah dicabut.'], 422);
        }

        if (($certificate->status ?? 'active') === 'expired') {
            return response()->json(['message' => 'Peringatan: Masa berlaku sertifikat telah habis (Kedaluwarsa).'], 422);
        }

        $revokedAt = isset($data['revoked_at']) ? Carbon::parse($data['revoked_at']) : now();

        if ($revokedAt->isFuture()) {
            return response()->json(['message' => 'Peringatan: Tanggal pencabutan sertifikat tidak boleh melebihi waktu saat ini.'], 422);
        }

        if ($certificate->issue_date && $revokedAt->lt($certificate->issue_date->copy()->startOfDay())) {
            return response()->json(['message' => 'Peringatan: Tanggal pencabutan sertifikat tidak boleh lebih awal dari tanggal terbit.'], 422);
        }

        return DB::transaction(function () use ($certificate, $data, $revokedAt) {
            CertificateRevocation::query()->updateOrCreate(
                ['certificate_id' => $certificate->id],
                [
                    'revoked_at' => $revokedAt,
                    'reason' => $data['reason'],
                ]
            );

            $certificate->status = 'revoked';
            $certificate->save();

            return response()->json([
                'message' => 'Sukses: Sertifikat berhasil dicabut.',
                'data' => $certificate->load(['revocation']),
            ]);
        });
    }

    public function unrevoke(Certificate $certificate): JsonResponse
    {
        if ($certificate->status !== 'revoked') {
            return response()->json(['message' => 'Peringatan: Sertifikat ini belum dalam status dicabut.'], 422);
        }

        return DB::transaction(function () use ($certificate) {
            CertificateRevocation::query()
                ->where('certificate_id', $certificate->id)
                ->delete();

            $status = $certificate->valid_until && $certificate->valid_until->lt(today())
                ? 'expired'
                : 'active';

            $certificate->status = $status;
            $certificate->save();

            return response()->json([
                'message' => $status === 'active'
                    ? 'Sukses: Sertifikat berhasil diaktifkan kembali.'
                    : 'Sukses: Pencabutan sertifikat dibatalkan, tetapi sertifikat sudah kedaluwarsa.',
                'data' => $certificate->fresh()->load(['revocation']),
            ]);
        });
    }

    public function sign(
        Certificate $certificate,
        CertificateSigningService $signingService,
        CertificatePdfIntegrityService $pdfIntegrity
    ): JsonResponse {
        if (($certificate->status ?? 'active') !== 'active') {
            return response()->json([
                'message' => 'Peringatan: Hanya sertifikat aktif yang dapat ditandatangani.',
            ], 422);
        }

        try {
            $sig = $signingService->sign($certificate, true);
            $pdfIntegrity->clearOfficialPdfIntegrity($certificate);

            return response()->json([
                'message' => 'Sukses: Sertifikat berhasil ditandatangani.',
                'data' => $sig->load('rsaKey'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal: Sertifikat gagal ditandatangani. Pastikan RSA Key aktif telah dikonfigurasi dengan benar.',
            ], 422);
        }
    }

    public function printAuthenticity(Certificate $certificate, CertificatePrintService $printer): View|JsonResponse
    {
        return $printer->render($certificate, 'BIBIT_UNGGUL');
    }

    public function printBirth(Certificate $certificate, CertificatePrintService $printer): View|JsonResponse
    {
        return $printer->render($certificate, 'KELAHIRAN');
    }

    public function printDeath(Certificate $certificate, CertificatePrintService $printer): View|JsonResponse
    {
        return $printer->render($certificate, 'KEMATIAN');
    }

    public function print(Certificate $certificate, CertificatePrintService $printer): View|JsonResponse
    {
        return $printer->render($certificate);
    }

    private function serviceResponse(array $result): JsonResponse
    {
        if (($result['ok'] ?? false) !== true) {
            return response()->json(['message' => $result['message']], $result['status'] ?? 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status'] ?? 200);
    }
}
