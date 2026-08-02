<?php

use App\Http\Controllers\Admin\AdminResourceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FarmProfileController;
use App\Http\Controllers\AuthSessionController;
use App\Http\Controllers\PublicVerificationController;
use Illuminate\Support\Facades\Route;

$publicLocales = implode('|', array_keys(config('public.locales', [])));

Route::redirect('/', '/'.config('public.default_locale', 'id-id'));

Route::middleware('public.locale')
    ->prefix('{locale}')
    ->where(['locale' => $publicLocales])
    ->group(function (): void {
        Route::get('/', [PublicVerificationController::class, 'index'])->name('verification');
        Route::post('/verifikasi', [PublicVerificationController::class, 'verify'])->name('verification.submit');
        Route::get('/hasil-verifikasi', [PublicVerificationController::class, 'result'])->name('verification.result');
        Route::view('/sertifikat-elektronik', 'pages.public.certificate-info')->name('certificate.info');
        Route::view('/lokasi', 'pages.public.location')->name('location');
        Route::get('/tentang', fn (string $locale) => redirect("/{$locale}#tentang"))->name('about');
    });

Route::get('/login', [AuthSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.submit');
Route::get('/lupa-kata-sandi', [AuthSessionController::class, 'forgotPassword'])->name('password.request');
Route::post('/lupa-kata-sandi', [AuthSessionController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-kata-sandi/{token}', [AuthSessionController::class, 'resetPassword'])->name('password.reset');
Route::post('/reset-kata-sandi', [AuthSessionController::class, 'updatePassword'])->name('password.update');
Route::post('/logout', [AuthSessionController::class, 'destroy'])->name('logout');

Route::middleware('bbh.auth')->group(function () {
    Route::redirect('/admin', '/admin/dashboard');

    Route::get('/admin/dashboard', DashboardController::class)->name('admin.dashboard');
    Route::get('/admin/profile', [FarmProfileController::class, 'show'])->name('admin.profile');
    Route::put('/admin/profile', [FarmProfileController::class, 'update'])->name('admin.profile.update');
    Route::put('/admin/profile/password', [FarmProfileController::class, 'updatePassword'])->name('admin.profile.password');

    foreach (array_keys(config('admin.pages', [])) as $resource) {
        Route::get("/admin/{$resource}", [AdminResourceController::class, 'index'])
            ->defaults('resource', $resource)
            ->name("admin.{$resource}");
    }

    Route::get('/admin/certificates/{id}/preview-frame', [AdminResourceController::class, 'previewCertificate'])
        ->name('admin.certificates.preview-frame');
    Route::get('/admin/certificates/{id}/pdf', [AdminResourceController::class, 'downloadCertificate'])
        ->name('admin.certificates.pdf');
    Route::get('/admin/reports/{report}/xlsx', [AdminResourceController::class, 'downloadReport'])
        ->name('admin.reports.xlsx');

    Route::get('/admin/breeding-females/{id}/exit', [AdminResourceController::class, 'exitBreedingFemaleForm'])
        ->name('admin.breeding-females.exit');
    Route::post('/admin/breeding-females/{id}/exit', [AdminResourceController::class, 'exitBreedingFemale'])
        ->name('admin.breeding-females.exit.store');
    Route::get('/admin/breeding-females/{id}/mating', [AdminResourceController::class, 'matingBreedingFemaleForm'])
        ->name('admin.breeding-females.mating');
    Route::post('/admin/breeding-females/{id}/mating', [AdminResourceController::class, 'matingBreedingFemale'])
        ->name('admin.breeding-females.mating.store');

    Route::get('/admin/{resource}/create', [AdminResourceController::class, 'create'])->name('admin.resource.create');
    Route::post('/admin/{resource}', [AdminResourceController::class, 'store'])->name('admin.resource.store');
    Route::put('/admin/{resource}/{id}', [AdminResourceController::class, 'update'])->name('admin.resource.update');
    Route::post('/admin/{resource}/{id}/action/{action}', [AdminResourceController::class, 'action'])->name('admin.resource.action');
    Route::get('/admin/{resource}/{id}', [AdminResourceController::class, 'show'])->name('admin.resource.show');
    Route::get('/admin/{resource}/{id}/edit', [AdminResourceController::class, 'edit'])->name('admin.resource.edit');
});
