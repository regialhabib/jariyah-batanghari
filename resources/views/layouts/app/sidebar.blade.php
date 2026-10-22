@php
    /** @var \App\Models\User $pengguna */
    $pengguna = auth()->user();

    // Menu sidebar per role (docs/02-peran-hak-akses.md). 'aktif' = pola nama rute untuk penanda menu.
    $menu = $pengguna->isNazir()
        ? [
            ['Dashboard', 'home', route('nazir.dashboard'), 'nazir.dashboard'],
            ['Data Wakaf Saya', 'map-pin', route('nazir.tanah'), 'nazir.tanah'],
            ['Pengajuan', 'clipboard-document-list', route('nazir.pengajuan.index'), 'nazir.pengajuan.*'],
            ['Dokumen', 'folder', route('nazir.dokumen'), 'nazir.dokumen'],
            ['Peta Wakaf', 'map', route('nazir.peta'), 'nazir.peta'],
        ]
        : array_values(array_filter([
            ['Dashboard', 'home', route('admin.dashboard'), 'admin.dashboard'],
            ['Data Wakaf', 'map-pin', route('admin.tanah.index'), 'admin.tanah.*'],
            ['Pengajuan', 'clipboard-document-list', route('admin.pengajuan.index'), 'admin.pengajuan.*'],
            $pengguna->can('sertifikat.catat-terbit') ? ['Sertifikat', 'shield-check', route('admin.sertifikat'), 'admin.sertifikat'] : null,
            ['Peta Wakaf', 'map', route('admin.peta'), 'admin.peta'],
            $pengguna->can('laporan.lihat') ? ['Laporan', 'chart-bar', route('admin.laporan'), 'admin.laporan*'] : null,
            $pengguna->can('pesan.lihat') ? ['Pesan Masuk', 'inbox', route('admin.pesan'), 'admin.pesan'] : null,
        ]));

    $menuAdmin = $pengguna->isAdmin()
        ? [
            ['Pengguna', 'users', route('admin.pengguna.index'), 'admin.pengguna.*'],
            ['Kelola Konten', 'pencil-square', route('admin.konten.artikel'), 'admin.konten.*'],
            ['Log Aktivitas', 'clock', route('admin.aktivitas'), 'admin.aktivitas'],
        ]
        : [];

    $subjudul = $pengguna->peran()?->label() ?? '';
    if ($pengguna->isKua() && $pengguna->kecamatan) {
        $subjudul .= ' · '.$pengguna->kecamatan->nama;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-krem font-sans antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-primary-800 bg-primary-900 text-white">
            <flux:sidebar.header>
                <a href="{{ route('beranda') }}" wire:navigate class="flex items-center gap-2 px-1">
                    <x-logo putih />
                </a>
                <flux:sidebar.collapse class="text-white lg:hidden" />
            </flux:sidebar.header>

            <div class="flex items-center gap-3 rounded-lg bg-primary-800/60 px-3 py-2.5">
                <flux:avatar :name="$pengguna->name" :initials="$pengguna->initials()" size="sm" class="bg-primary-600! text-white!" />
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-semibold">{{ $pengguna->name }}</p>
                    <p class="truncate text-xs text-primary-200">{{ $subjudul }}</p>
                </div>
            </div>

            <nav class="flex flex-col gap-0.5" aria-label="Menu dashboard">
                @foreach ($menu as [$label, $ikon, $url, $aktif])
                    <a href="{{ $url }}" wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-white text-primary-800 shadow-sm' => request()->routeIs($aktif),
                            'text-primary-100 hover:bg-primary-800' => ! request()->routeIs($aktif),
                        ])
                        @if (request()->routeIs($aktif)) aria-current="page" @endif>
                        <flux:icon :name="$ikon" class="size-5" /> {{ $label }}
                    </a>
                @endforeach

                @if ($menuAdmin)
                    <p class="mt-4 px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-primary-300">Administrasi</p>
                    @foreach ($menuAdmin as [$label, $ikon, $url, $aktif])
                        <a href="{{ $url }}" wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-white text-primary-800 shadow-sm' => request()->routeIs($aktif),
                                'text-primary-100 hover:bg-primary-800' => ! request()->routeIs($aktif),
                            ])>
                            <flux:icon :name="$ikon" class="size-5" /> {{ $label }}
                        </a>
                    @endforeach
                @endif

                <a href="{{ route('profile.edit') }}" wire:navigate
                    @class([
                        'mt-4 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-white text-primary-800 shadow-sm' => request()->routeIs('profile.edit', 'security.edit'),
                        'text-primary-100 hover:bg-primary-800' => ! request()->routeIs('profile.edit', 'security.edit'),
                    ])>
                    <flux:icon.user-circle class="size-5" /> Profil
                </a>
            </nav>

            <flux:spacer />

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-primary-100 hover:bg-primary-800" data-test="logout-button">
                    <flux:icon.arrow-right-start-on-rectangle class="size-5" /> Keluar
                </button>
            </form>
        </flux:sidebar>

        {{-- Topbar --}}
        <flux:header class="sticky top-0 z-30 border-b border-primary-100 bg-white/95! backdrop-blur">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <livewire:notifikasi-lonceng />

            <flux:dropdown position="bottom" align="end">
                <flux:profile :initials="$pengguna->initials()" :name="$pengguna->name" icon-trailing="chevron-down" />

                <flux:menu>
                    <div class="px-2 py-1.5 text-sm leading-tight">
                        <p class="font-semibold">{{ $pengguna->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $pengguna->email }}</p>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('beranda')" icon="globe-alt" wire:navigate>Halaman Publik</flux:menu.item>
                    <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>Profil</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                            Keluar
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
