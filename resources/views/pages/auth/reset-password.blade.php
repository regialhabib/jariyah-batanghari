<x-layouts::auth
    title="Atur Ulang Password"
    judul="Buat password baru"
    deskripsi="Gunakan password yang kuat dan mudah Anda ingat untuk menjaga keamanan akun."
    ilustrasi="lanskap"
>
    <x-auth-header
        icon="lock-closed"
        title="Atur Ulang Password"
        description="Masukkan password baru untuk akun Anda."
    />

    <x-auth-session-status :status="session('status')" />

    @php($aturanPassword = \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString())

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <flux:input
            name="email"
            :value="old('email', request('email'))"
            label="Email"
            type="email"
            icon="envelope"
            required
            autocomplete="email"
        />

        <flux:input
            name="password"
            label="Password Baru"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Minimal 8 karakter"
            :passwordrules="$aturanPassword"
            viewable
        />

        <flux:input
            name="password_confirmation"
            label="Konfirmasi Password"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Ulangi password baru"
            :passwordrules="$aturanPassword"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
            Simpan Password Baru
        </flux:button>
    </form>

    <p class="text-center text-sm text-zinc-600">
        <flux:link :href="route('login')" class="font-semibold" wire:navigate>Kembali ke Login</flux:link>
    </p>
</x-layouts::auth>
