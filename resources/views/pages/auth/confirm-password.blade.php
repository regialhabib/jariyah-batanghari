<x-layouts::auth
    title="Konfirmasi Password"
    judul="Area aman"
    deskripsi="Demi keamanan akun, konfirmasi password Anda sebelum melanjutkan."
    ilustrasi="lanskap"
>
    <x-auth-header
        icon="shield-check"
        title="Konfirmasi Password"
        description="Ini adalah area aman. Masukkan password Anda untuk melanjutkan."
    />

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
        @csrf

        <flux:input
            name="password"
            label="Password"
            type="password"
            icon="lock-closed"
            required
            autofocus
            autocomplete="current-password"
            placeholder="Masukkan password"
            viewable
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
            Konfirmasi
        </flux:button>
    </form>
</x-layouts::auth>
