@props(['slug', 'id', 'row', 'record'])

<div class="flex flex-nowrap justify-end gap-2">
    @if ($slug === 'certificates')
        <a class="ui-btn ui-btn-primary h-9 px-3" href="{{ route('admin.resource.show', ['resource' => $slug, 'id' => $id]) }}">
            <x-icons name="file" class="h-4 w-4" />
            Unduh
        </a>
        @if (($row[4] ?? '') === 'Dicabut')
            <form method="post" action="{{ route('admin.resource.action', ['resource' => $slug, 'id' => $id, 'action' => 'unrevoke']) }}">
                @csrf
                <button class="ui-btn ui-btn-soft h-9 px-3" type="submit">Aktifkan Kembali</button>
            </form>
        @else
            <form method="post" action="{{ route('admin.resource.action', ['resource' => $slug, 'id' => $id, 'action' => 'revoke']) }}">
                @csrf
                <button class="ui-btn ui-btn-danger-soft h-9 px-3" type="submit">Cabut Sertifikat</button>
            </form>
        @endif
    @elseif ($slug === 'pregnancy-checks')
        <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.resource.show', ['resource' => $slug, 'id' => $id]) }}" aria-label="Detail" title="Detail">
            <x-icons name="eye" class="h-4 w-4" />
        </a>
    @else
        <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.resource.show', ['resource' => $slug, 'id' => $id]) }}" aria-label="Detail" title="Detail">
            <x-icons name="eye" class="h-4 w-4" />
        </a>
        <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.resource.edit', ['resource' => $slug, 'id' => $id]) }}" aria-label="Edit" title="Edit">
            <x-icons name="edit" class="h-4 w-4" />
        </a>
        @if ($slug === 'breeding-females' && empty(data_get($record, 'raw.exit_date')))
            <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.breeding-females.mating', ['id' => $id]) }}" aria-label="Catat Kawin" title="Catat Kawin">
                <x-icons name="calendar" class="h-4 w-4" />
            </a>
            <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ route('admin.breeding-females.exit', ['id' => $id]) }}" aria-label="Keluarkan dari periode" title="Keluarkan dari periode">
                <x-icons name="logout" class="h-4 w-4" />
            </a>
        @endif
    @endif
</div>
