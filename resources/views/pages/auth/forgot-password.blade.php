<x-layouts::auth
    title="Lupa Password"
    judul="Lupa password Anda?"
    deskripsi="Tenang, kami akan mengirimkan tautan untuk membuat password baru ke email yang terdaftar."
    ilustrasi="lanskap"
>
    <x-auth-header
        icon="key"
        title="Lupa Password"
        description="Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password."
    />

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <flux:input
            name="email"
            label="Email"
            :value="old('email')"
            type="email"
            icon="envelope"
            required
            autofocus
            autocomplete="email"
            placeholder="nama@email.com"
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
            Kirim Tautan Reset Password
        </flux:button>
    </form>

    <p class="text-center text-sm text-zinc-600">
        Sudah ingat password?
        <flux:link :href="route('login')" class="font-semibold" wire:navigate>Kembali ke Login</flux:link>
    </p>
</x-layouts::auth>
