<x-layouts.guest title="Reset Kata Sandi">
    <main class="min-h-screen bg-[var(--app-bg)] text-[var(--app-text)]">
        <button class="ui-btn ui-btn-soft fixed right-5 top-5 z-20 h-10 w-10 px-0" type="button" aria-label="Ganti tema" data-theme-toggle>
            <x-icons name="moon" class="h-5 w-5 dark:hidden" />
            <x-icons name="sun" class="hidden h-5 w-5 dark:block" />
        </button>

        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(520px,0.78fr)]">
            <section class="hidden items-center justify-center border-r border-[var(--app-border)] bg-white px-10 dark:bg-[#101828] lg:flex">
                <div class="max-w-lg">
                    <img src="{{ asset('logo-main.webp') }}" alt="Bumiku Bumimu Hijau Farm" class="h-20 w-20 object-contain">
                    <p class="mt-7 text-sm font-semibold text-[var(--app-accent)]">Keamanan Akun</p>
                    <h1 class="mt-3 text-[32px] font-semibold leading-tight text-gray-900 dark:text-white">
                        Buat kata sandi baru untuk akun pengelola
                    </h1>
                    <p class="mt-5 text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Gunakan kata sandi yang kuat agar akses dashboard tetap aman dan mudah dikelola.
                    </p>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-[420px]">
                    <a href="{{ route('login') }}" class="mb-10 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <x-icons name="arrow-left" class="h-4 w-4" />
                        Kembali ke login
                    </a>

                    <div class="mb-8">
                        <h1 class="text-[32px] font-semibold leading-tight text-gray-900 dark:text-white">Reset Kata Sandi</h1>
                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Buat kata sandi baru untuk akun admin.</p>
                    </div>

                    <form class="space-y-5" method="post" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        @if ($errors->any())
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                                <p class="font-semibold">Gagal</p>
                                <p class="mt-1">{{ $errors->first() }}</p>
                            </div>
                        @endif

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</span>
                            <input class="ui-input" type="email" name="email" value="{{ old('email', $email) }}" placeholder="admin@example.com" required>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata Sandi Baru</span>
                            <input class="ui-input" type="password" name="password" placeholder="Minimal 8 karakter" required>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Kata Sandi</span>
                            <input class="ui-input" type="password" name="password_confirmation" placeholder="Ulangi kata sandi baru" required>
                        </label>

                        <button class="ui-btn ui-btn-farm w-full" type="submit">
                            Simpan Kata Sandi
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </main>
</x-layouts.guest>
