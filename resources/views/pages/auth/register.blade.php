<x-layouts::auth
    title="Daftar Akun Nazir"
    judul="Bergabung bersama Jariyah Batang Hari"
    deskripsi="Kelola data wakaf, pantau status pengajuan, dan akses informasi dengan lebih mudah."
    ilustrasi="masjid"
    lebar
>
    <x-auth-header
        title="Daftar Akun"
        description="Pendaftaran khusus Nazir. Setelah mendaftar, verifikasi email Anda untuk mulai menggunakan sistem."
    />

    <x-auth-session-status :status="session('status')" />

    @php($aturanPassword = \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString())

    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input
                name="name"
                label="Nama Lengkap"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Nama sesuai KTP"
            />

            <flux:input
                name="nik"
                label="NIK"
                :value="old('nik')"
                type="text"
                inputmode="numeric"
                maxlength="16"
                required
                autocomplete="off"
                placeholder="16 digit NIK"
            />

            <flux:input
                name="email"
                label="Email"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="nama@email.com"
            />

            <flux:input
                name="phone"
                label="No. HP"
                :value="old('phone')"
                type="tel"
                inputmode="tel"
                required
                autocomplete="tel"
                placeholder="08xxxxxxxxxx"
            />

            <flux:input
                name="password"
                label="Password"
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
                placeholder="Ulangi password"
                :passwordrules="$aturanPassword"
                viewable
            />
        </div>

        <flux:checkbox
            name="terms"
            value="1"
            label="Saya menyetujui Syarat dan Ketentuan yang berlaku"
            :checked="(bool) old('terms')"
            required
        />

        <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
            Daftar
        </flux:button>
    </form>

    <p class="text-center text-sm text-zinc-600">
        Sudah punya akun?
        <flux:link :href="route('login')" class="font-semibold" wire:navigate>Login di sini</flux:link>
    </p>
</x-layouts::auth>
