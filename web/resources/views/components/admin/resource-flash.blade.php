@if ($errors->any())
    <div class="admin-alert admin-alert-danger">
        <p class="font-semibold">Gagal</p>
        <p class="mt-1 theme-muted">Periksa kembali informasi berikut sebelum melanjutkan.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
