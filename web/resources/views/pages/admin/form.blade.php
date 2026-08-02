<x-layouts.admin :title="$pageTitle" skeleton="form">
    <div class="admin-form-shell">
    <x-panel :title="$pageTitle">
        @if ($errors->any())
            <div class="admin-alert admin-alert-danger">
                <p class="font-semibold">Gagal</p>
                <p class="mt-1 theme-muted">Periksa kembali informasi berikut sebelum menyimpan data.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="grid gap-5" method="post" enctype="multipart/form-data" action="{{ $mode === 'edit' ? route('admin.resource.update', ['resource' => $slug, 'id' => $id]) : route('admin.resource.store', ['resource' => $slug]) }}">
            @csrf
            @if ($mode === 'edit')
                @method('put')
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($fields as $field)
                    <x-admin.form-field :field="$field" :mode="$mode" :values="$values ?? []" />
                @endforeach
            </div>

            <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: var(--app-border);">
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.' . $slug) }}">
                    <x-icons name="x" class="h-4 w-4" />
                    Batal
                </a>
                <button class="ui-btn ui-btn-primary" type="submit">
                    <x-icons name="save" class="h-4 w-4" />
                    {{ $slug === 'certificates' && $mode === 'create' ? 'Terbitkan Sertifikat' : 'Simpan' }}
                </button>
            </div>
        </form>
    </x-panel>
    </div>

</x-layouts.admin>
