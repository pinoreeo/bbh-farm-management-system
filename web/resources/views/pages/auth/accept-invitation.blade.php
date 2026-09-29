<x-layouts.guest title="Aktivasi Akun" :force-light="true">
    <main class="auth-workspace auth-clickup-canvas min-h-screen text-[var(--app-text)]">
        <section class="flex min-h-screen items-center justify-center px-5 py-16">
            <div class="w-full max-w-[390px]">
                <div class="mb-6 flex flex-col items-center text-center">
                    <img src="{{ asset('logo-main.webp') }}" alt="Bumiku Bumimu Hijau Farm" class="h-11 w-11 object-contain">
                    <h1 class="auth-title mt-4">Buat password</h1>
                    <p class="auth-helper-text mt-2">Buat password untuk mengaktifkan akun Anda.</p>
                </div>

                <form class="mx-auto grid gap-3" method="post" action="{{ route('admin-invitation.accept.submit') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    @if ($errors->any())
                        <div class="auth-alert rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                            <p class="auth-alert-title">Aktivasi Gagal</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <label class="block">
                        <span class="sr-only">Email</span>
                        <input class="auth-login-input" type="email" name="email" value="{{ old('email', $email) }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
                    </label>

                    <label class="block">
                        <span class="sr-only">Password</span>
                        <input class="auth-login-input" type="password" name="password" placeholder="Buat password" autocomplete="new-password" required>
                    </label>

                    <label class="block">
                        <span class="sr-only">Konfirmasi Password</span>
                        <input class="auth-login-input" type="password" name="password_confirmation" placeholder="Konfirmasi password" autocomplete="new-password" required>
                    </label>

                    <button class="auth-login-button" type="submit">Aktifkan akun</button>
                </form>
            </div>
        </section>
    </main>
</x-layouts.guest>
