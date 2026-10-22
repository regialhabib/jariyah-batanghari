@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'beranda', 'aktif' => 'beranda'],
        ['label' => 'Tentang', 'route' => 'tentang', 'aktif' => 'tentang'],
        ['label' => 'Informasi Wakaf', 'route' => 'informasi.index', 'aktif' => 'informasi.*'],
        ['label' => 'Persyaratan', 'route' => 'persyaratan', 'aktif' => 'persyaratan'],
        ['label' => 'Peta Wakaf', 'route' => 'peta', 'aktif' => 'peta*'],
        ['label' => 'Panduan', 'route' => 'panduan', 'aktif' => 'panduan'],
        ['label' => 'FAQ', 'route' => 'faq', 'aktif' => 'faq'],
        ['label' => 'Kontak', 'route' => 'kontak', 'aktif' => 'kontak'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-zinc-800 antialiased">
        <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Lewati ke konten</a>

        {{-- Header --}}
        <header x-data="{ buka: false }" class="sticky top-0 z-40 border-b border-primary-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('beranda') }}" wire:navigate aria-label="Jariyah Batang Hari — Beranda">
                    <x-logo />
                </a>

                <nav class="hidden items-center gap-1 xl:flex" aria-label="Menu utama">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate
                            @class([
                                'rounded-md px-2.5 py-2 text-sm font-medium transition',
                                'text-primary-700 underline decoration-2 underline-offset-8' => request()->routeIs($item['aktif']),
                                'text-zinc-700 hover:text-primary-700' => ! request()->routeIs($item['aktif']),
                            ])
                            @if (request()->routeIs($item['aktif'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-2 xl:flex">
                    <a href="{{ route('peta') }}" wire:navigate class="rounded-md p-2 text-zinc-600 hover:text-primary-700" aria-label="Cari tanah wakaf">
                        <flux:icon.magnifying-glass class="size-5" />
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary !px-4 !py-2">
                            <flux:icon.squares-2x2 class="size-4" /> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-outline !px-4 !py-2">Login</a>
                        <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2">Daftar</a>
                    @endauth
                </div>

                <button type="button" class="rounded-md p-2 text-primary-800 xl:hidden" x-on:click="buka = ! buka"
                    x-bind:aria-expanded="buka" aria-controls="menu-hp" aria-label="Buka menu">
                    <flux:icon.bars-3 class="size-6" x-show="! buka" />
                    <flux:icon.x-mark class="size-6" x-show="buka" x-cloak />
                </button>
            </div>

            {{-- Menu HP --}}
            <nav id="menu-hp" x-show="buka" x-collapse x-cloak class="border-t border-primary-100 bg-white xl:hidden" aria-label="Menu utama">
                <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate
                            @class([
                                'block rounded-md px-3 py-2 text-sm font-medium',
                                'bg-primary-50 text-primary-700' => request()->routeIs($item['aktif']),
                                'text-zinc-700 hover:bg-primary-50' => ! request()->routeIs($item['aktif']),
                            ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <div class="flex gap-2 pt-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary flex-1">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-outline flex-1">Login</a>
                            <a href="{{ route('register') }}" class="btn-primary flex-1">Daftar</a>
                        @endauth
                    </div>
                </div>
            </nav>
        </header>

        <main id="konten" class="flex-1">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="bg-primary-900 text-primary-100">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
                <div class="space-y-3">
                    <x-logo putih />
                    <p class="text-sm text-primary-200">Sistem Informasi Pengelolaan dan Monitoring Tanah Wakaf Kabupaten Batang Hari</p>
                </div>

                <div>
                    <h2 class="mb-3 text-sm font-semibold text-white">Navigasi</h2>
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                        @foreach ($menu as $item)
                            <li><a href="{{ route($item['route']) }}" wire:navigate class="hover:text-white">{{ $item['label'] }}</a></li>
                        @endforeach
                        <li><a href="{{ route('login') }}" class="hover:text-white">Login / Daftar</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="mb-3 text-sm font-semibold text-white">Kontak Kami</h2>
                    <ul class="space-y-2 text-sm">
                        <li class="flex gap-2"><flux:icon.map-pin class="mt-0.5 size-4 shrink-0" /> <span>{{ setting('alamat') }}</span></li>
                        <li class="flex gap-2"><flux:icon.phone class="mt-0.5 size-4 shrink-0" /> <span>{{ setting('telepon') }}</span></li>
                        <li class="flex gap-2"><flux:icon.envelope class="mt-0.5 size-4 shrink-0" /> <span class="break-all">{{ setting('email') }}</span></li>
                    </ul>
                </div>

                <div>
                    <h2 class="mb-3 text-sm font-semibold text-white">Ikuti Kami</h2>
                    <div class="flex gap-2">
                        @foreach (['sosial_facebook' => 'Facebook', 'sosial_instagram' => 'Instagram', 'sosial_youtube' => 'YouTube'] as $kunci => $nama)
                            @if (filled(setting($kunci)))
                                <a href="{{ setting($kunci) }}" target="_blank" rel="noopener" class="flex size-9 items-center justify-center rounded-full bg-primary-700 text-xs font-semibold text-white hover:bg-primary-600" aria-label="{{ $nama }}">
                                    {{ substr($nama, 0, 1) }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm text-primary-200">{{ setting('jam_operasional') }}</p>
                </div>
            </div>
            <div class="border-t border-primary-800">
                <p class="mx-auto max-w-7xl px-4 py-4 text-xs text-primary-300 sm:px-6 lg:px-8">&copy; {{ now()->year }} Jariyah Batang Hari — Kantor Pertanahan Kabupaten Batang Hari. Semua hak cipta dilindungi.</p>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
