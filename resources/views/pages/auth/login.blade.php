<x-layouts::auth
    title="Login"
    judul="Selamat Datang di Jariyah Batang Hari"
    deskripsi="Silakan login untuk mengakses sistem sesuai dengan akun Anda."
    ilustrasi="lanskap"
>
    <x-auth-header title="Login" description="Masuk ke akun Anda untuk mengelola data dan pengajuan wakaf." />

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf

        <flux:input
            name="email"
            label="Email / Username"
            :value="old('email')"
            type="text"
            icon="user"
            required
            autofocus
            autocomplete="username"
            placeholder="Masukkan email atau username"
        />

        <flux:input
            name="password"
            label="Password"
            type="password"
            icon="lock-closed"
            required
            autocomplete="current-password"
            placeholder="Masukkan password"
            viewable
        />

        <div class="flex items-center justify-between gap-4">
            <flux:checkbox name="remember" label="Ingat saya" :checked="(bool) old('remember')" />

            @if (Route::has('password.request'))
                <flux:link class="text-sm font-medium" :href="route('password.request')" wire:navigate>
                    Lupa password?
                </flux:link>
            @endif
        </div>

        <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
            Login
        </flux:button>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-sm text-zinc-600">
            Belum punya akun?
            <flux:link :href="route('register')" class="font-semibold" wire:navigate>Daftar di sini</flux:link>
        </p>
    @endif
</x-layouts::auth>
