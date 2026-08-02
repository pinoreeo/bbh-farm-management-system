@if (session('formMessage') || $errors->any())
    <div class="admin-alert {{ $errors->any() ? 'admin-alert-danger' : 'admin-alert-success' }}">
        <p class="font-semibold">{{ $errors->any() ? 'Gagal' : 'Sukses' }}</p>
        @if ($errors->any())
            <p class="mt-1 theme-muted">Periksa kembali informasi berikut sebelum melanjutkan.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @else
            <p class="mt-1 theme-muted">{{ session('formMessage') }}</p>
        @endif
    </div>
@endif
