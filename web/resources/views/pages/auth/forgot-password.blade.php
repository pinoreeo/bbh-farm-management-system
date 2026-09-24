<x-layouts.guest title="Lupa Kata Sandi">
    <main class="auth-workspace auth-clickup-canvas min-h-screen text-[var(--app-text)]">
        <button class="ui-btn ui-btn-soft fixed right-5 top-5 z-20 h-10 w-10 px-0" type="button" aria-label="Ganti tema" data-theme-toggle>
            <x-icons name="moon" class="h-5 w-5 dark:hidden" />
            <x-icons name="sun" class="hidden h-5 w-5 dark:block" />
        </button>

        <a href="{{ route('login') }}" class="auth-back-link fixed left-5 top-5 z-20 hidden items-center gap-2 sm:inline-flex">
            <x-icons name="arrow-left" class="h-4 w-4" />
            Kembali ke login
        </a>

        <section class="flex min-h-screen items-center justify-center px-5 py-16">
            <div class="w-full max-w-[390px]">
                <div class="mb-6 flex flex-col items-center text-center">
                    <img src="{{ asset('logo-main.webp') }}" alt="Bumiku Bumimu Hijau Farm" class="h-11 w-11 object-contain">
                    <h1 class="auth-title mt-4">Lupa kata sandi?</h1>
                    <p class="auth-helper-text mt-2">
                        Ingat kata sandi?
                        <a href="{{ route('login') }}" class="auth-link">Masuk</a>
                    </p>
                </div>

                <form class="mx-auto grid gap-3" method="post" action="{{ route('password.email') }}">
                    @csrf

                    @if (session('status'))
                        <div class="auth-alert rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300">
                            <p class="auth-alert-title">Permintaan reset telah diterima</p>
                            <p class="mt-1">{{ session('status') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-alert rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                            <p class="auth-alert-title">Gagal</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <label class="block">
                        <span class="sr-only">Email</span>
                        <input class="auth-login-input" type="email" name="email" value="{{ old('email') }}" placeholder="Email pengelola" autocomplete="username" required autofocus>
                    </label>

                    <button class="auth-login-button" type="submit">
                        Kirim tautan reset
                    </button>
                </form>
            </div>
        </section>

    </main>
</x-layouts.guest>
