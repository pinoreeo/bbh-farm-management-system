@php
    $isEdit = $mode === 'edit';
    $formTitle = $isEdit ? 'Edit ' . $recordTitle : $pageTitle;
    [$createSubtitle, $editSubtitle, $cardSubtitle] = match ($slug) {
        'animals' => ['Catat identitas kambing.', 'Perbarui identitas kambing ini.', 'Identitas, asal, dan status kambing yang dicatat.'],
        'weight-records' => ['Catat hasil penimbangan kambing.', 'Perbarui catatan penimbangan kambing ini.', 'Kambing, tanggal timbang, dan hasil penimbangan.'],
        'pens' => ['Tambahkan data kandang dan koloni.', 'Perbarui data kandang dan koloni ini.', 'Identitas koloni, lokasi, dan kapasitas kandang.'],
        'pen-movements' => ['Catat perpindahan kambing antar koloni.', 'Perbarui catatan perpindahan kambing ini.', 'Kambing, koloni tujuan, tanggal, dan alasan perpindahan.'],
        'breeding-periods' => ['Catat periode perkawinan.', 'Perbarui data periode perkawinan ini.', 'Kandang, pejantan, dan tanggal periode perkawinan.'],
        'breeding-females' => ['Catat kambing betina yang masuk periode perkawinan.', 'Perbarui catatan betina pada periode perkawinan ini.', $isEdit ? 'Tanggal masuk dan tahap siklus betina yang dicatat.' : 'Betina, periode perkawinan, dan tanggal masuk.'],
        'birth-events' => ['Catat kelahiran kambing.', 'Perbarui catatan kelahiran kambing ini.', 'Induk, waktu, jumlah anak, dan proses kelahiran.'],
        'offspring-births' => ['Catat data cempe yang lahir.', 'Perbarui catatan cempe ini.', $isEdit ? 'Bobot lahir, grade, dan status hidup cempe yang dicatat.' : 'Identitas, bobot lahir, dan status hidup cempe yang dicatat.'],
        'health-treatments' => ['Catat pemeriksaan dan perawatan kambing.', 'Perbarui catatan pemeriksaan dan perawatan kambing ini.', 'Gejala, diagnosis, obat, dan perawatan yang dicatat.'],
        'vaccinations' => ['Catat vaksinasi kambing.', 'Perbarui catatan vaksinasi kambing ini.', 'Kambing, tanggal, vaksin, dan pemberian yang dicatat.'],
        'postnatal-care' => ['Catat perawatan cempe setelah lahir.', 'Perbarui catatan perawatan cempe ini.', 'Pemberian kolostrum, perawatan pusar, vitamin, dan obat.'],
        'certificates' => ['Isi data untuk penerbitan akte atau sertifikat kambing.', 'Perbarui data akte atau sertifikat kambing ini.', 'Kambing, jenis dokumen, dan tempat penerbitan.'],
        'rsa-keys' => ['Buat kunci digital untuk sertifikat.', 'Perbarui data kunci digital ini.', 'Identitas kunci digital untuk sertifikat.'],
        default => ['Tambahkan catatan baru.', 'Perbarui catatan ini.', 'Data yang akan disimpan.'],
    };
    $formSubtitle = $isEdit ? $editSubtitle : $createSubtitle;
    $cancelRoute = $isEdit
        ? ($slug === 'animals'
            ? route('admin.animals.show', ['tag' => data_get($values ?? [], 'tag_number')])
            : route('admin.resource.show', ['resource' => $slug, 'id' => $id]))
        : route('admin.' . $slug);
@endphp

<x-layouts.admin :title="$pageTitle" skeleton="form" :page-header="false">
    <x-admin.record-page-header
        :collection="$collectionTitle"
        :collection-route="route('admin.' . $slug)"
        :title="$formTitle"
        :subtitle="$formSubtitle"
        :record="$recordTitle ?? null"
        :mode="$isEdit ? 'Edit' : 'Tambah'"
    />

    <div class="admin-form-shell">
        <form class="admin-form" method="post" enctype="multipart/form-data" action="{{ $mode === 'edit' ? route('admin.resource.update', ['resource' => $slug, 'id' => $id]) : route('admin.resource.store', ['resource' => $slug]) }}" data-skeleton-target="table">
                @csrf
                @if ($mode === 'edit')
                    @method('put')
                @endif

            <x-panel :title="'Informasi ' . $collectionTitle" :subtitle="$cardSubtitle" :padded="false" class="admin-form-panel">
                <div class="admin-form-panel-body">
                    @if ($errors->any())
                        <div class="admin-alert admin-alert-danger">
                            <p class="font-semibold">Gagal</p>
                            <p class="mt-1 theme-muted">Periksa kembali isian yang ditandai sebelum menyimpan.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($fields as $field)
                            <x-admin.form-field :field="$field" :mode="$mode" :values="$values ?? []" />
                        @endforeach
                    </div>
                </div>
            </x-panel>

            <div class="admin-form-actions">
                <button class="ui-btn ui-btn-primary" type="submit">
                    <x-icons name="save" class="h-4 w-4" />
                    {{ $slug === 'certificates' && $mode === 'create' ? 'Terbitkan Sertifikat' : ($isEdit ? 'Simpan Perubahan' : 'Tambah Data') }}
                </button>
                <a class="ui-btn ui-btn-soft" href="{{ $cancelRoute }}">
                    <x-icons name="x" class="h-4 w-4" />
                    Batal
                </a>
            </div>
        </form>
    </div>

</x-layouts.admin>
