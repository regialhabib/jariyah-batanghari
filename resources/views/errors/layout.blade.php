{{-- Tata letak halaman error. Sengaja tidak memakai layout publik/dashboard dan tidak menyentuh database,
     supaya tetap tampil saat database atau sesi bermasalah. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
        <meta name="robots" content="noindex">
    </head>
    <body class="flex min-h-screen flex-col bg-krem font-sans text-zinc-800 antialiased">
        <header class="border-b border-primary-100 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" aria-label="Jariyah Batang Hari — Beranda"><x-logo /></a>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-16">
            <div class="kartu w-full max-w-lg p-8 text-center sm:p-10">
                <x-ikon-kotak :icon="$ikon" size="lg" variant="soft" class="mx-auto" />
                <p class="mt-6 text-5xl font-bold text-primary-700">{{ $kode }}</p>
                <h1 class="mt-2 text-xl font-semibold text-primary-900">{{ $title }}</h1>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $pesan }}</p>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ url('/') }}" class="btn-primary">Ke Beranda</a>
                    @if ($kembali ?? true)
                        <a href="{{ url()->previous() }}" class="btn-outline">Kembali</a>
                    @endif
                </div>
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-zinc-500">
            &copy; {{ date('Y') }} Jariyah Batang Hari · Kantor Pertanahan Kabupaten Batang Hari
        </footer>
    </body>
</html>
