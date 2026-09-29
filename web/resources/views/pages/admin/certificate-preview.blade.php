<x-layouts.admin title="Preview Sertifikat" skeleton="detail" :page-header="false">
    @php($certificateStatus = $row[4] ?? '-')
    <x-admin.record-page-header
        collection="Akte & Sertifikat"
        :collection-route="route('admin.certificates')"
        title="Preview Sertifikat"
        subtitle="Lihat dokumen sertifikat sebelum diunduh."
        :record="$row[0] ?? null"
        mode="Preview"
    />
    <div class="admin-page-actions">
        <a class="ui-btn ui-btn-primary" href="{{ route('admin.certificates.pdf', ['id' => $id]) }}" data-no-skeleton>
            <x-icons name="download" class="h-4 w-4" />
            Unduh PDF
        </a>
    </div>

    @if ($errors->any())
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Dokumen belum siap</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-panel title="Preview {{ $row[2] ?? 'Sertifikat' }}">
        <x-slot:actions>
            <span class="admin-resource-card-status" data-tone="{{ $certificateStatus === 'Aktif' ? 'positive' : ($certificateStatus === 'Dicabut' ? 'negative' : 'neutral') }}">
                {{ $certificateStatus }}
            </span>
        </x-slot:actions>

        <iframe class="block h-[70vh] max-h-[760px] min-h-[420px] w-full rounded-md border border-[var(--app-border)] bg-white" title="Pratinjau sertifikat" src="{{ route('admin.certificates.preview-frame', ['id' => $id]) }}"></iframe>
    </x-panel>
</x-layouts.admin>
