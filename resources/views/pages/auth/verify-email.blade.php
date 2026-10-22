<x-layouts::auth
    title="Verifikasi Email"
    judul="Satu langkah lagi"
    deskripsi="Verifikasi email Anda agar dapat mengelola data wakaf dan mengajukan sertifikat."
    ilustrasi="masjid"
>
    <x-auth-header
        icon="envelope"
        title="Verifikasi Email"
        description="Kami telah mengirim tautan verifikasi ke email Anda. Klik tautan tersebut untuk mengaktifkan akun."
    />

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status status="Tautan verifikasi baru telah dikirim ke email yang Anda daftarkan." />
    @endif

    @auth
        <div class="rounded-lg bg-krem px-4 py-3 text-sm text-zinc-600">
            Email terdaftar: <span class="font-semibold text-primary-900">{{ auth()->user()->email }}</span>
        </div>
    @endauth

    <flux:text class="text-sm">
        Tidak menerima email? Periksa folder spam, atau kirim ulang tautan verifikasi.
    </flux:text>

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <flux:button type="submit" variant="primary" class="w-full">
                Kirim Ulang Email Verifikasi
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="w-full cursor-pointer text-sm" data-test="logout-button">
                Keluar
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
