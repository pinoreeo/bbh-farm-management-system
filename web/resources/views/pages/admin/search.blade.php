<x-layouts.admin title="Pencarian Data" skeleton="table">
    @php($searchTitle = $query !== '' ? 'Hasil data untuk "'.$query.'"' : 'Cari Data')

    <div class="grid gap-4">
        <x-panel title="Pencarian Data">
            <form class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]" method="get" action="{{ route('admin.search') }}" data-skeleton-target="table">
                <label>
                    <span class="sr-only">Cari data peternakan</span>
                    <input class="ui-input" type="search" name="q" value="{{ $query }}" placeholder="Cari eartag, nomor sertifikat, kandang, vaksinasi...">
                </label>
                <button class="ui-btn ui-btn-primary" type="submit">
                    <x-icons name="search" class="h-4 w-4" />
                    Cari
                </button>
            </form>
        </x-panel>

        @if (! empty($failureMessage))
            <div class="admin-alert admin-alert-danger">
                <p class="font-semibold">Sebagian Data Tidak Dapat Dimuat</p>
                <p class="mt-1 theme-muted">{{ $failureMessage }}</p>
            </div>
        @endif

        <x-panel :title="$searchTitle">
            <div class="divide-y divide-[var(--app-border)]">
                @forelse ($results as $item)
                    <div class="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="admin-meta-text uppercase">{{ $item['title'] }}</p>
                            <p class="mt-1 text-sm font-medium text-[var(--app-text)]">{{ $item['primary'] }}</p>
                            @if (! empty($item['secondary']))
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-[var(--app-muted)]">{{ $item['secondary'] }}</p>
                            @endif
                            @if (! empty($item['matchedFields']))
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($item['matchedFields'] as $field)
                                        <span class="rounded-full border border-[var(--app-border)] px-3 py-1 text-xs text-[var(--app-muted)]">
                                            {{ $field['label'] }}: <span class="font-medium text-[var(--app-text)]">{{ $field['value'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a class="ui-btn ui-btn-soft" href="{{ $item['listRoute'] }}" data-skeleton-target="table">
                                Lihat Daftar
                            </a>
                            <a class="ui-btn ui-btn-primary" href="{{ $item['detailRoute'] }}" data-skeleton-target="detail">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <p class="text-sm font-medium text-[var(--app-text)]">{{ $query === '' ? 'Masukkan kata kunci untuk mencari data.' : 'Tidak ada data yang cocok.' }}</p>
                        <p class="mt-1 text-sm text-[var(--app-muted)]">Coba cari eartag, nomor sertifikat, kode kandang, nama koloni, tanggal, atau status data.</p>
                    </div>
                @endforelse
            </div>
        </x-panel>
    </div>
</x-layouts.admin>
