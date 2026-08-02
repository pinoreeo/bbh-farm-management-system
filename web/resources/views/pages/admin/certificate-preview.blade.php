<x-layouts.admin title="Preview Sertifikat" skeleton="detail">
    <div class="admin-page-actions">
        <a class="ui-btn ui-btn-soft" href="{{ route('admin.certificates') }}">
            <x-icons name="arrow-left" class="h-4 w-4" />
            Kembali
        </a>

        <a class="ui-btn ui-btn-primary" href="{{ route('admin.certificates.pdf', ['id' => $id]) }}" data-no-skeleton>
            <x-icons name="file" class="h-4 w-4" />
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
            <span class="ui-badge" style="background: color-mix(in oklab, var(--app-success) 14%, transparent); color: var(--app-success);">
                {{ $row[4] ?? 'Aktif' }}
            </span>
        </x-slot:actions>

        <div class="mx-auto max-w-5xl rounded-lg border p-3 sm:p-5" style="border-color: var(--app-border); background: var(--app-surface-soft);">
            <iframe class="h-[760px] w-full rounded-md border bg-white" style="border-color: #E5E7EB;" src="{{ route('admin.certificates.preview-frame', ['id' => $id]) }}"></iframe>
        </div>
    </x-panel>
</x-layouts.admin>
