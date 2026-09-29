@if (session('formMessage'))
    @php
        $message = (string) session('formMessage');
        $tone = str_starts_with($message, 'Gagal:') ? 'danger' : (str_starts_with($message, 'Info:') ? 'info' : 'success');
        $message = preg_replace('/^(Sukses|Info|Gagal):\s*/', '', $message) ?: $message;
    @endphp

    <div class="admin-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="admin-toast" data-tone="{{ $tone }}" data-flash-toast role="status">
            <x-icons :name="$tone === 'success' ? 'check' : 'bell'" class="h-4 w-4 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    </div>
@endif
