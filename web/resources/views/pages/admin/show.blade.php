<x-layouts.admin :title="$pageTitle" skeleton="detail" :page-header="false">
    @php
        $cardCount = $slug === 'users' ? min(2, max(1, count($columns))) : min(3, max(1, count($columns)));
        $fieldsPerCard = max(1, (int) ceil(count($columns) / $cardCount));
        $detailCards = array_chunk($columns, $fieldsPerCard, true);
        $detailSubtitle = match ($slug) {
            'users' => 'Rincian pengguna dan status akunnya.',
            'weight-records' => 'Rincian catatan penimbangan kambing.',
            'pens' => 'Rincian data kandang dan koloni.',
            'pen-movements' => 'Rincian catatan perpindahan kambing.',
            'breeding-females' => 'Catatan kambing betina pada periode perkawinan ini.',
            'birth-events' => 'Rincian catatan kelahiran kambing.',
            'offspring-births' => 'Rincian data cempe yang lahir.',
            'health-treatments' => 'Rincian catatan pemeriksaan dan perawatan kambing.',
            'vaccinations' => 'Rincian catatan vaksinasi kambing.',
            'postnatal-care' => 'Rincian catatan perawatan cempe setelah lahir.',
            'certificate-logs' => 'Rincian riwayat pemeriksaan sertifikat.',
            'activity-logs' => 'Rincian aktivitas pengguna yang tercatat.',
            'rsa-keys' => 'Rincian kunci digital untuk sertifikat.',
            default => 'Rincian data yang telah dicatat.',
        };
        $cardTitles = match ($slug) {
            'users' => ['Informasi Pengguna', 'Akses & Aktivitas'],
            'weight-records' => ['Penimbangan', 'Hasil Penimbangan', 'Catatan'],
            'pens' => ['Identitas Kandang & Koloni', 'Rincian Kandang', 'Status Kandang'],
            'pen-movements' => ['Kambing & Koloni Asal', 'Perpindahan', 'Alasan'],
            'breeding-females' => ['Betina & Periode', 'Catatan Perkawinan', 'Keluar Periode'],
            'birth-events' => ['Induk & Pejantan', 'Rincian Kelahiran', 'Tempat Kelahiran'],
            'offspring-births' => ['Identitas Cempe', 'Berat & Grade', 'Kondisi Cempe'],
            'health-treatments' => ['Catatan Perawatan', 'Pemeriksaan & Obat', 'Tindak Lanjut'],
            'vaccinations' => ['Kambing & Vaksin', 'Catatan Vaksinasi', 'Pemberian Vaksin'],
            'postnatal-care' => ['Cempe & Perawatan', 'Kolostrum & Pusar', 'Vitamin & Obat'],
            default => ['Informasi ' . $collectionTitle, 'Rincian Data', 'Keterangan'],
        };
        $cardSubtitles = match ($slug) {
            'users' => ['Nama dan role pengguna.', 'Status akun dan aktivitas terakhir.'],
            'weight-records' => ['Kambing dan tanggal penimbangan.', 'Bobot dan umur saat ditimbang.', 'Catatan tambahan penimbangan.'],
            'pens' => ['Kode kandang dan identitas koloni.', 'Fase koloni, lokasi, dan kapasitas kandang.', 'Status penggunaan kandang.'],
            'pen-movements' => ['Kambing dan koloni sebelum dipindahkan.', 'Koloni tujuan dan tanggal perpindahan.', 'Alasan perpindahan yang dicatat.'],
            'breeding-females' => ['Betina, periode, dan tanggal masuk.', 'Tanggal kawin, perkiraan lahir, dan tahap siklus.', 'Tanggal dan alasan keluar dari periode.'],
            'birth-events' => ['Induk, pejantan, dan tanggal kelahiran.', 'Waktu, jumlah anak, dan proses kelahiran.', 'Lokasi kelahiran yang dicatat.'],
            'offspring-births' => ['Eartag cempe dan tanggal kelahiran.', 'Bobot lahir dan grade yang dicatat.', 'Status hidup dan catatan cempe.'],
            'health-treatments' => ['Kambing, tanggal, dan jenis perawatan.', 'Gejala, diagnosis, dan nama obat yang dicatat.', 'Dosis obat dan tanggal kontrol berikutnya.'],
            'vaccinations' => ['Kambing dan jenis vaksin yang dicatat.', 'Tanggal vaksinasi dan nama vaksin.', 'Dosis dan cara pemberian yang dicatat.'],
            'postnatal-care' => ['Data kelahiran, cempe, dan tanggal perawatan.', 'Pemberian kolostrum dan perawatan pusar.', 'Vitamin dan obat yang dicatat.'],
            default => [],
        };
        $cardIcons = ['file', 'activity', 'settings'];
        $gridClass = count($detailCards) === 1
            ? 'admin-record-card-grid grid gap-4'
            : (count($detailCards) === 2
                ? 'admin-record-card-grid grid gap-4 lg:grid-cols-2'
                : 'admin-record-card-grid grid gap-4 md:grid-cols-2 xl:grid-cols-3');
    @endphp

    <x-admin.record-page-header
        :collection="$collectionTitle"
        :collection-route="route('admin.' . $slug)"
        :title="$recordTitle"
        :subtitle="$detailSubtitle"
        :record="$recordTitle"
        :edit-route="route('admin.resource.edit', ['resource' => $slug, 'id' => $id])"
    />

    <div class="{{ $gridClass }}">
        @foreach ($detailCards as $cardIndex => $fields)
            <x-panel
                :title="$cardTitles[$cardIndex] ?? 'Informasi Tambahan'"
                :subtitle="$cardSubtitles[$cardIndex] ?? null"
                :title-icon="$cardIcons[$cardIndex] ?? 'file'"
                class="admin-record-detail-card"
            >
                <dl class="admin-record-detail-list">
                    @foreach (array_chunk($fields, 2, true) as $detailRow)
                        <div class="admin-record-detail-row">
                            @foreach ($detailRow as $index => $column)
                                <div>
                                    <dt>{{ $column }}</dt>
                                    <dd>{{ $row[$index] ?? '-' }}</dd>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </dl>
            </x-panel>
        @endforeach
    </div>

    @if (count($history) > 0)
        <div class="mt-5">
            <x-admin.record-history :history="$history" :collection="$collectionTitle" />
        </div>
    @endif
</x-layouts.admin>
