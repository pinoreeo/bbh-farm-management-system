@props(['slug', 'id', 'row', 'record'])

@php
    $animalTag = $slug === 'animals' ? data_get($record, 'raw.tag_number') : null;
    $showRoute = $animalTag
        ? route('admin.animals.show', ['tag' => $animalTag])
        : route('admin.resource.show', ['resource' => $slug, 'id' => $id]);
    $editRoute = $animalTag
        ? route('admin.animals.edit', ['tag' => $animalTag])
        : route('admin.resource.edit', ['resource' => $slug, 'id' => $id]);
@endphp

<div class="flex flex-nowrap justify-end gap-2">
    @if ($slug === 'certificates')
        <details class="admin-row-menu" data-row-menu>
            <summary aria-label="Buka aksi sertifikat" title="Aksi">
                <x-icons name="more" class="h-5 w-5" />
            </summary>
            <div class="admin-row-menu-panel">
                <a href="{{ route('admin.resource.show', ['resource' => $slug, 'id' => $id]) }}">
                    <x-icons name="eye" class="h-4 w-4" />
                    Lihat detail
                </a>
                <a href="{{ route('admin.resource.edit', ['resource' => $slug, 'id' => $id]) }}">
                    <x-icons name="edit" class="h-4 w-4" />
                    Edit data
                </a>
                <a href="{{ route('admin.certificates.pdf', ['id' => $id]) }}" data-no-skeleton>
                    <x-icons name="download" class="h-4 w-4" />
                    Unduh sertifikat
                </a>
                @if (($row[4] ?? '') === 'Dicabut')
                    <form method="post" action="{{ route('admin.resource.action', ['resource' => $slug, 'id' => $id, 'action' => 'unrevoke']) }}" data-skeleton-target="table">
                        @csrf
                        <button class="admin-row-menu-action" type="submit">
                            <x-icons name="check" class="h-4 w-4" />
                            Aktifkan kembali
                        </button>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.resource.action', ['resource' => $slug, 'id' => $id, 'action' => 'revoke']) }}" data-skeleton-target="table">
                        @csrf
                        <button class="admin-row-menu-action is-danger" type="submit">
                            <x-icons name="circle-x" class="h-4 w-4" />
                            Cabut sertifikat
                        </button>
                    </form>
                @endif
            </div>
        </details>
    @elseif ($slug === 'pregnancy-checks')
        <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.resource.show', ['resource' => $slug, 'id' => $id]) }}" aria-label="Detail" title="Detail">
            <x-icons name="eye" class="h-4 w-4" />
        </a>
    @else
        <details class="admin-row-menu" data-row-menu>
            <summary aria-label="Buka aksi baris" title="Aksi">
                <x-icons name="more" class="h-5 w-5" />
            </summary>
            <div class="admin-row-menu-panel">
                <a href="{{ $showRoute }}">
                    <x-icons name="eye" class="h-4 w-4" />
                    Lihat detail
                </a>
                <a href="{{ $editRoute }}">
                    <x-icons name="edit" class="h-4 w-4" />
                    Edit data
                </a>
                @if ($slug === 'breeding-females' && empty(data_get($record, 'raw.exit_date')))
                    <a href="{{ route('admin.breeding-females.mating', ['id' => $id]) }}">
                        <x-icons name="heart" class="h-4 w-4" />
                        Catat kawin
                    </a>
                    <a href="{{ route('admin.breeding-females.exit', ['id' => $id]) }}">
                        <x-icons name="logout" class="h-4 w-4" />
                        Keluarkan dari periode
                    </a>
                @endif
            </div>
        </details>
    @endif
</div>
