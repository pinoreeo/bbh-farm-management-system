<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CertificatePdfIntegrityService
{
    public function __construct(
        private readonly CertificateSigningService $signingService,
        private readonly CertificateViewDataService $viewData,
    ) {}

    public function ensureOfficialPdf(Certificate $certificate, bool $force = false): Certificate
    {
        $certificate = $this->loadForPdf($certificate);

        if (! $force && $this->hasStoredOfficialPdf($certificate)) {
            return $certificate;
        }

        $rendered = $this->render($certificate);
        $hash = hash('sha256', $rendered['bytes']);
        $signedHash = $this->signingService->signHash($hash);
        $path = $this->storagePathFor($certificate, $rendered['filename']);

        if (! Storage::disk('local')->put($path, $rendered['bytes'])) {
            throw new \RuntimeException('Failed to store official certificate PDF.');
        }

        $certificate->forceFill([
            'official_pdf_path' => $path,
            'official_pdf_hash_sha256' => $hash,
            'official_pdf_signature_base64' => $signedHash['signature_base64'],
            'official_pdf_signature_scheme' => config('bbh_signing.signature_scheme', 'RSA-SHA256'),
            'official_pdf_rsa_key_id' => $signedHash['rsa_key']->id,
            'official_pdf_signed_at' => now(),
            'official_pdf_generated_at' => now(),
        ])->save();

        return $this->loadForPdf($certificate->fresh());
    }

    public function clearOfficialPdfIntegrity(Certificate $certificate): void
    {
        $certificate->forceFill([
            'official_pdf_path' => null,
            'official_pdf_hash_sha256' => null,
            'official_pdf_signature_base64' => null,
            'official_pdf_signature_scheme' => null,
            'official_pdf_rsa_key_id' => null,
            'official_pdf_signed_at' => null,
            'official_pdf_generated_at' => null,
        ])->save();
    }

    public function verifyOfficialPdfSignature(Certificate $certificate): bool
    {
        $certificate->loadMissing('officialPdfRsaKey');

        if (
            ! $certificate->official_pdf_hash_sha256 ||
            ! $certificate->official_pdf_signature_base64 ||
            ! $certificate->officialPdfRsaKey
        ) {
            return false;
        }

        return $this->signingService->verifyHash(
            (string) $certificate->official_pdf_hash_sha256,
            (string) $certificate->official_pdf_signature_base64,
            (string) $certificate->officialPdfRsaKey->public_key_pem
        );
    }

    public function officialPdfAbsolutePath(Certificate $certificate): string
    {
        if (! $certificate->official_pdf_path) {
            throw new \RuntimeException('Official PDF has not been generated.');
        }

        return Storage::disk('local')->path($certificate->official_pdf_path);
    }

    public function downloadFilenameFor(Certificate $certificate, array $data): string
    {
        $title = match ($certificate->certificateType?->type_code) {
            'BIBIT_UNGGUL' => 'Sertifikat-Bibit-Unggul',
            'KELAHIRAN' => 'Akta-Kelahiran',
            'KEMATIAN' => 'Akta-Kematian',
            default => 'Sertifikat',
        };

        $certificateNumber = $this->filenameSegment($certificate->certificate_number ?: 'Tanpa-Nomor');
        $issueDate = $this->filenameSegment($data['issue_date'] ?? now()->format('d-m-Y'));

        return "{$title}_{$certificateNumber}_{$issueDate}.pdf";
    }

    public function loadForPdf(Certificate $certificate): Certificate
    {
        $certificate->load([
            'animal.breed',
            'animal.currentPen',
            'certificateType',
            'birthEvent.dam.breed',
            'birthEvent.sire.breed',
            'birthEvent.offspringBirths',
            'birthEvent.postnatalCareRecords',
            'signature.rsaKey',
            'officialPdfRsaKey',
            'revocation',
        ]);

        return $certificate;
    }

    public function bladeViewFor(Certificate $certificate): ?string
    {
        return match ($certificate->certificateType?->type_code) {
            'KEMATIAN' => 'certificates.pdf_akte_kematian',
            'KELAHIRAN' => 'certificates.pdf_akte_kelahiran',
            'BIBIT_UNGGUL' => 'certificates.pdf_sertifikat_hewan',
            default => null,
        };
    }

    public function paperFor(Certificate $certificate): array|string
    {
        return match ($certificate->certificateType?->type_code) {
            'BIBIT_UNGGUL' => [0, 0, 487.5, 675],
            'KELAHIRAN' => [0, 0, 525, 780],
            'KEMATIAN' => [0, 0, 525, 780],
            default => 'a4',
        };
    }

    public function render(Certificate $certificate): array
    {
        $certificate = $this->loadForPdf($certificate);

        $bladeView = $this->bladeViewFor($certificate);
        if (! $bladeView) {
            throw new \RuntimeException('Invalid certificate type.');
        }

        $this->validateRenderable($certificate);

        $data = $this->viewData->build($certificate);
        $qr = $certificate->certificateType->type_code === 'BIBIT_UNGGUL'
            ? $this->viewData->makeQrBase64($data['verification_url'])
            : null;

        $viewPayload = [
            'certificate' => $certificate,
            'data' => $data,
            'qr' => $qr,
            'assets' => $this->templateAssets(),
        ];

        $bytes = $this->renderWithBrowser($bladeView, $viewPayload);

        return [
            'bytes' => $bytes,
            'data' => $data,
            'filename' => $this->downloadFilenameFor($certificate, $data),
        ];
    }

    private function hasStoredOfficialPdf(Certificate $certificate): bool
    {
        if (
            ! $certificate->official_pdf_path ||
            ! $certificate->official_pdf_hash_sha256 ||
            ! $certificate->official_pdf_signature_base64 ||
            ! $certificate->official_pdf_rsa_key_id ||
            ! $certificate->official_pdf_generated_at ||
            ! Storage::disk('local')->exists($certificate->official_pdf_path)
        ) {
            return false;
        }

        $storedHash = hash_file('sha256', Storage::disk('local')->path($certificate->official_pdf_path));

        return is_string($storedHash) &&
            hash_equals((string) $certificate->official_pdf_hash_sha256, $storedHash) &&
            $this->isOfficialPdfFreshForTemplate($certificate);
    }

    private function storagePathFor(Certificate $certificate, string $filename): string
    {
        return 'certificates/official/'.$certificate->id.'/'.$filename;
    }

    public function templateAssets(): array
    {
        return [
            'logo' => $this->publicAssetDataUri('images/logo-bbh.png'),
            'signature' => $this->publicAssetDataUri('images/ttd-manajer.png'),
        ];
    }

    private function validateRenderable(Certificate $certificate): void
    {
        if (! $certificate->certificateType) {
            throw new \RuntimeException('Certificate type not found.');
        }

        if (! $certificate->animal) {
            throw new \RuntimeException('Animal data is required before exporting this certificate.');
        }

        $typeCode = $certificate->certificateType->type_code;

        if ($typeCode === 'BIBIT_UNGGUL' && ! $certificate->barcode_value) {
            throw new \RuntimeException('Barcode value is not available for this certificate.');
        }

        if ($typeCode === 'KELAHIRAN' && ! $certificate->birthEvent) {
            throw new \RuntimeException('Birth event data is required before exporting a birth certificate.');
        }

        if ($typeCode === 'KEMATIAN' && (! $certificate->death_date || ! $certificate->death_time || ! $certificate->cause_of_death)) {
            throw new \RuntimeException('Death date, time, and cause are required before exporting a death certificate.');
        }
    }

    private function publicAssetDataUri(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            return null;
        }

        $bytes = file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return 'data:'.$mimeType.';base64,'.base64_encode($bytes);
    }

    private function renderWithBrowser(string $bladeView, array $viewPayload): string
    {
        $browserPath = $this->browserExecutablePath();
        $workDir = storage_path('app/tmp/certificate-pdf');

        File::ensureDirectoryExists($workDir);

        $token = uniqid('certificate_', true);
        $htmlPath = $workDir.DIRECTORY_SEPARATOR.$token.'.html';
        $pdfPath = $workDir.DIRECTORY_SEPARATOR.$token.'.pdf';
        $userDataDir = $workDir.DIRECTORY_SEPARATOR.$token.'_chrome_profile';

        File::ensureDirectoryExists($userDataDir);

        File::put($htmlPath, view($bladeView, $viewPayload)->render());

        $process = new Process([
            $browserPath,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-crash-reporter',
            '--disable-crashpad',
            '--disable-extensions',
            '--no-first-run',
            '--no-default-browser-check',
            '--user-data-dir='.$userDataDir,
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=1000',
            '--print-to-pdf='.$pdfPath,
            '--print-to-pdf-no-header',
            'file:///'.str_replace('\\', '/', $htmlPath),
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($pdfPath)) {
            File::delete([$htmlPath, $pdfPath]);
            File::deleteDirectory($userDataDir);

            throw new \RuntimeException('Browser PDF rendering failed: '.$process->getErrorOutput());
        }

        $bytes = file_get_contents($pdfPath);
        File::delete([$htmlPath, $pdfPath]);
        File::deleteDirectory($userDataDir);

        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Browser PDF rendering produced an empty file.');
        }

        return $bytes;
    }

    private function browserExecutablePath(): string
    {
        $configuredPath = config('bbh.pdf.browser_path');
        if (is_string($configuredPath) && $configuredPath !== '' && is_file($configuredPath)) {
            return $configuredPath;
        }

        foreach ($this->defaultBrowserPaths() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Browser executable for PDF rendering is not configured. Set BBH_PDF_BROWSER_PATH to Chrome or Edge.');
    }

    private function defaultBrowserPaths(): array
    {
        return [
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files\BraveSoftware\Brave-Browser\Application\brave.exe',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA').'\BraveSoftware\Brave-Browser\Application\brave.exe' : '',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
        ];
    }

    private function isOfficialPdfFreshForTemplate(Certificate $certificate): bool
    {
        $generatedAt = $certificate->official_pdf_generated_at;
        if (! $generatedAt) {
            return false;
        }

        foreach ($this->templateDependencyPaths($certificate) as $path) {
            if (is_file($path) && filemtime($path) > $generatedAt->getTimestamp()) {
                return false;
            }
        }

        return true;
    }

    private function templateDependencyPaths(Certificate $certificate): array
    {
        $bladeView = $this->bladeViewFor($certificate);
        $paths = [
            public_path('images/logo-bbh.png'),
            public_path('images/ttd-manajer.png'),
        ];

        if ($bladeView) {
            $paths[] = resource_path('views/'.str_replace('.', '/', $bladeView).'.blade.php');
        }

        $paths[] = app_path('Services/CertificatePdfIntegrityService.php');

        return $paths;
    }

    private function filenameSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9\-]+/', '-', trim($value));
        $normalized = trim((string) $normalized, '-');

        return $normalized !== '' ? $normalized : 'Tidak-Tersedia';
    }
}
