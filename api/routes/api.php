<?php

use App\Http\Controllers\Api\V1\AdminActivityLogController;
use App\Http\Controllers\Api\V1\AnimalPenMovementController;
use App\Http\Controllers\Api\V1\AnimalController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BirthEventController;
use App\Http\Controllers\Api\V1\BreedController;
use App\Http\Controllers\Api\V1\BreedingFemaleController;
use App\Http\Controllers\Api\V1\BreedingPeriodController;
use App\Http\Controllers\Api\V1\CertificateController;
use App\Http\Controllers\Api\V1\CertificateExportController;
use App\Http\Controllers\Api\V1\CertificateRevocationController;
use App\Http\Controllers\Api\V1\CertificateSignatureController;
use App\Http\Controllers\Api\V1\CertificateTypeController;
use App\Http\Controllers\Api\V1\CertificateVerificationLogController;
use App\Http\Controllers\Api\V1\ColonyPenController;
use App\Http\Controllers\Api\V1\FarmProfileController;
use App\Http\Controllers\Api\V1\HealthTreatmentController;
use App\Http\Controllers\Api\V1\InbreedingCheckController;
use App\Http\Controllers\Api\V1\OffspringBirthController;
use App\Http\Controllers\Api\V1\PostnatalCareRecordController;
use App\Http\Controllers\Api\V1\PregnancyCheckController;
use App\Http\Controllers\Api\V1\Public\CertificateVerificationController;
use App\Http\Controllers\Api\V1\RsaKeyController;
use App\Http\Controllers\Api\V1\ReportExportController;
use App\Http\Controllers\Api\V1\UserManagementController;
use App\Http\Controllers\Api\V1\VaccinationController;
use App\Http\Controllers\Api\V1\WeightRecordController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });

    // Public Verification
    Route::prefix('public')->group(function () {
        Route::post('certificates/verify', [CertificateVerificationController::class, 'verify'])
            ->middleware('throttle:30,1');
        Route::post('certificates/verify-pdf', [CertificateVerificationController::class, 'verifyPdf'])
            ->middleware('throttle:10,1');
        Route::get('certificates/verify/{token}', [CertificateVerificationController::class, 'verifyByToken'])
            ->middleware('throttle:30,1');
        Route::get('certificates/{certificate_number}', [CertificateVerificationController::class, 'showPublic'])
            ->middleware('throttle:30,1');
    });

    // Admin
    Route::middleware(['auth:sanctum', IsAdmin::class, 'adminActivity'])->group(function () {

        Route::get('farm', [FarmProfileController::class, 'show']);
        Route::put('farm', [FarmProfileController::class, 'update']);
        Route::apiResource('users', UserManagementController::class)->except(['destroy']);

        Route::apiResource('breeds', BreedController::class)->except(['destroy']);
        Route::post('animals/{animal}', [AnimalController::class, 'update']);
        Route::apiResource('animals', AnimalController::class)->except(['destroy']);
        Route::apiResource('colony-pens', ColonyPenController::class)->except(['destroy']);
        Route::apiResource('animal-pen-movements', AnimalPenMovementController::class)->except(['destroy']);
        Route::get('inbreeding-check', InbreedingCheckController::class);
        Route::apiResource('breeding-periods', BreedingPeriodController::class)->except(['destroy']);
        Route::post('breeding-periods/{breedingPeriod}/close', [BreedingPeriodController::class, 'close']);
        Route::post('breeding-females/{breedingFemale}/mating', [BreedingFemaleController::class, 'recordMating']);
        Route::post('breeding-females/{breedingFemale}/exit', [BreedingFemaleController::class, 'exit']);
        Route::apiResource('breeding-females', BreedingFemaleController::class)->except(['destroy']);
        Route::apiResource('pregnancy-checks', PregnancyCheckController::class)->except(['destroy']);
        Route::apiResource('birth-events', BirthEventController::class)->except(['destroy']);
        Route::apiResource('offspring-births', OffspringBirthController::class)->except(['destroy']);

        Route::apiResource('postnatal-care-records', PostnatalCareRecordController::class)->except(['destroy']);

        Route::apiResource('weight-records', WeightRecordController::class)->except(['destroy']);
        Route::apiResource('health-treatments', HealthTreatmentController::class)->except(['destroy']);

        Route::apiResource('vaccinations', VaccinationController::class)->except(['destroy']);
        Route::get('reports/{report}/xlsx', ReportExportController::class);

        // Certificates
        Route::apiResource('certificate-types', CertificateTypeController::class)
            ->only(['index', 'show', 'update']);

        Route::apiResource('certificates', CertificateController::class)->except(['destroy']);

        // Certificate actions
        Route::post('certificates/{certificate}/sign', [CertificateController::class, 'sign']);
        Route::post('certificates/{certificate}/revoke', [CertificateController::class, 'revoke']);
        Route::post('certificates/{certificate}/unrevoke', [CertificateController::class, 'unrevoke']);

        // Certificate exports
        Route::get('certificates/{certificate}/qr', [CertificateExportController::class, 'qr']);
        Route::get('certificates/{certificate}/preview', [CertificateExportController::class, 'preview']);
        Route::get('certificates/{certificate}/pdf', [CertificateExportController::class, 'pdf']);
        Route::get('certificates/{certificate}/print', [CertificateController::class, 'print']);
        Route::get('certificates/{certificate}/print/authenticity', [CertificateController::class, 'printAuthenticity']);
        Route::get('certificates/{certificate}/print/birth', [CertificateController::class, 'printBirth']);
        Route::get('certificates/{certificate}/print/death', [CertificateController::class, 'printDeath']);

        // RSA keys
        Route::apiResource('rsa-keys', RsaKeyController::class)->except(['destroy']);
        Route::post('rsa-keys/generate', [RsaKeyController::class, 'generate']);
        Route::post('rsa-keys/{rsaKey}/activate', [RsaKeyController::class, 'activate']);
        Route::post('rsa-keys/{rsaKey}/deactivate', [RsaKeyController::class, 'deactivate']);
        Route::post('rsa-keys/{rsaKey}/compromise', [RsaKeyController::class, 'compromise']);

        // Read-only certificate support data
        Route::get('certificate-signatures', [CertificateSignatureController::class, 'index']);
        Route::get('certificate-signatures/{certificateSignature}', [CertificateSignatureController::class, 'show']);

        Route::get('certificate-revocations', [CertificateRevocationController::class, 'index']);
        Route::get('certificate-revocations/{certificateRevocation}', [CertificateRevocationController::class, 'show']);

        Route::get('certificate-verification-logs', [CertificateVerificationLogController::class, 'index']);
        Route::get('certificate-verification-logs/{certificateVerificationLog}', [CertificateVerificationLogController::class, 'show']);

        Route::get('admin-activity-logs', [AdminActivityLogController::class, 'index']);
        Route::get('admin-activity-logs/{adminActivityLog}', [AdminActivityLogController::class, 'show']);
    });
});
