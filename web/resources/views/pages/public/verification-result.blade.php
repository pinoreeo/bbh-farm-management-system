<x-layouts.guest title="Hasil Verifikasi Sertifikat" :force-light="true">
    @php
        $isValid = (bool) ($verificationResult['is_valid'] ?? false);
        $certificateType = $verificationResult['certificate_type'] ?? '-';
        $certificateTypeLabel = $verificationResult['certificate_type_name'] ?? match ($certificateType) {
            'BIBIT_UNGGUL' => 'Sertifikat Bibit Unggul Ternak',
            'KELAHIRAN' => 'Akta Kelahiran Ternak',
            'KEMATIAN' => 'Akta Kematian Ternak',
            default => str_replace('_', ' ', (string) $certificateType),
        };
        $animal = $verificationResult['animal'] ?? ($verificationResult['certificate_verification']['animal'] ?? []);
        $animal = is_array($animal) ? $animal : [];

        $formatDate = function ($value, $withTime = false) {
            if (empty($value)) {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        };

        $shortHash = function ($value) {
            if (empty($value)) {
                return '-';
            }

            $value = (string) $value;
            return strlen($value) > 28 ? substr($value, 0, 20).'...'.substr($value, -8) : $value;
        };

        $humanize = fn ($value) => $value ? str($value)->replace('_', ' ')->title()->toString() : '-';
        $sexLabel = match ($animal['sex'] ?? null) {
            'male' => 'Jantan',
            'female' => 'Betina',
            default => '-',
        };
        $lifeStatusLabel = match ($animal['life_status'] ?? null) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => '-',
        };
        $certificateStatusLabel = match ($verificationResult['certificate_status'] ?? null) {
            'active' => 'Aktif',
            'revoked' => 'Dicabut',
            'expired' => 'Tidak Aktif',
            default => '-',
        };
        $signatureInfo = $verificationResult['official_pdf_signature_info']
            ?? $verificationResult['signature_info']
            ?? ($verificationResult['certificate_verification']['official_pdf_signature_info'] ?? null)
            ?? ($verificationResult['certificate_verification']['signature_info'] ?? []);
        $signatureInfo = is_array($signatureInfo) ? $signatureInfo : [];
        $hasCertificateData = ! empty($verificationResult['certificate_number'])
            || ! empty($verificationResult['certificate_type'])
            || ! empty($verificationResult['certificate_status']);
        $hasAnimalData = ! empty($verificationResult['animal_tag'])
            || ! empty($animal['tag_number'])
            || ! empty($animal['sex'])
            || ! empty($animal['birth_date']);

        $certificateRows = [
            ['Nomor Sertifikat', $verificationResult['certificate_number'] ?? '-'],
            ['Jenis Sertifikat', $certificateTypeLabel],
            ['Status Sertifikat', $certificateStatusLabel],
            ['Tanggal Terbit', $formatDate($verificationResult['issue_date'] ?? null)],
            ['Tempat Terbit', $verificationResult['issue_place'] ?? '-'],
            ['Berlaku Mulai', $formatDate($verificationResult['valid_from'] ?? null)],
            ['Berlaku Sampai', $formatDate($verificationResult['valid_until'] ?? null)],
            ['Metode Verifikasi', $verificationResult['method_label'] ?? '-'],
        ];

        if (! empty($uploadedFilename)) {
            $certificateRows[] = ['File PDF', $uploadedFilename];
        }

        $animalRows = [
            ['Nomor Eartag', $verificationResult['animal_tag'] ?? ($animal['tag_number'] ?? '-')],
            ['Ras', $animal['breed_name'] ?? '-'],
            ['Jenis Kelamin', $sexLabel],
            ['Generasi', $animal['generation'] ?? '-'],
            ['Tanggal Lahir', $formatDate($animal['birth_date'] ?? null)],
            ['Tempat Lahir', $animal['birth_place'] ?? '-'],
            ['Umur', $animal['umur'] ?? '-'],
            ['Kategori Umur', $humanize($animal['kategori_umur'] ?? null)],
            ['Status Hidup', $lifeStatusLabel],
        ];

        $cryptoRows = [
            ['Keaslian Data', isset($verificationResult['is_authentic']) ? ((bool) $verificationResult['is_authentic'] ? 'Autentik' : 'Tidak autentik') : ($verificationResult['certificate_data_label'] ?? '-')],
            ['Ditandatangani oleh', $signatureInfo['signed_by'] ?? '-'],
            ['Waktu Tanda Tangan', $formatDate($signatureInfo['signed_at'] ?? null, true)],
        ];
        $cryptoRows = array_values(array_filter($cryptoRows, fn ($row) => ! empty($row[1]) && $row[1] !== '-'));

        $pdfRows = [
            ['Format File', $verificationResult['pdf_file_type_label'] ?? null],
            ['Keaslian PDF', $verificationResult['pdf_integrity_label'] ?? null],
            ['Isi PDF', $verificationResult['pdf_hash_label'] ?? null],
            ['Tanda Tangan PDF', $verificationResult['pdf_signature_label'] ?? null],
            ['Hash PDF Upload', $shortHash($verificationResult['uploaded_pdf_hash_sha256'] ?? null)],
            ['Hash PDF Resmi', $shortHash($verificationResult['official_pdf_hash_sha256'] ?? null)],
        ];
        $pdfRows = array_values(array_filter($pdfRows, fn ($row) => ! empty($row[1]) && $row[1] !== '-'));
    @endphp

    <div class="bbh-public bbh-verification-result flex min-h-screen flex-col bg-[#f5f7f1] text-[#101820]">
        <x-public.navbar />

        <main class="bbh-result-shell mx-auto flex-1 px-5 pb-16 pt-36 sm:px-8 lg:pb-20 lg:pt-40">
            <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="bbh-result-eyebrow">{{ $verificationResult['method_label'] ?? 'Verifikasi Sertifikat' }}</p>
                    <h1 class="bbh-result-title mt-2">Hasil Verifikasi Sertifikat</h1>
                </div>
                <a class="bbh-result-action inline-flex w-fit" href="{{ route('verification') }}">Verifikasi Lagi</a>
            </div>

            <x-public.result-status :is-valid="$isValid" :result="$verificationResult" />

            @if ($hasCertificateData || $hasAnimalData)
                <div class="mt-8 grid gap-7 lg:grid-cols-2">
                    @if ($hasCertificateData)
                        <x-public.result-detail-card title="Identitas Sertifikat" :rows="$certificateRows" />
                    @endif

                    @if ($hasAnimalData)
                        <x-public.result-detail-card
                            title="Identitas Kambing"
                            :rows="$animalRows"
                        />
                    @endif
                </div>

                @if (count($cryptoRows) > 0)
                    <div class="mt-7">
                        <x-public.result-detail-card
                            title="Tanda Tangan Digital"
                            description="Ringkasan keaslian sertifikat yang dapat diverifikasi publik."
                            :rows="$cryptoRows"
                        />
                    </div>
                @endif
            @elseif (! empty($uploadedFilename))
                <x-public.result-detail-card
                    class="mt-8"
                    title="Informasi Dokumen"
                    :rows="[
                        ['Metode Verifikasi', $verificationResult['method_label'] ?? 'Upload PDF'],
                        ['File PDF', $uploadedFilename],
                    ]"
                />
            @endif

            @if (count($pdfRows) > 0)
                <x-public.result-pdf-checks :rows="$pdfRows" />
            @endif
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
