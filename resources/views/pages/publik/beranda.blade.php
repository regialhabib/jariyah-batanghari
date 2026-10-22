<?php

use App\Services\StatistikWakaf;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Beranda')] class extends Component {
    /**
     * Kartu statistik publik (hanya tanah yang dipublikasikan).
     *
     * @return list<array{icon: string, label: string, value: int, satuan: string}>
     */
    #[Computed]
    public function statistik(): array
    {
        $ringkasan = app(StatistikWakaf::class)->ringkasan();

        return [
            ['icon' => 'map', 'label' => 'Total Tanah Wakaf', 'value' => $ringkasan['total'], 'satuan' => 'Bidang'],
            ['icon' => 'shield-check', 'label' => 'Bersertifikat', 'value' => $ringkasan['sudah'], 'satuan' => 'Bidang'],
            ['icon' => 'clock', 'label' => 'Belum Bersertifikat', 'value' => $ringkasan['belum'] + $ringkasan['proses'], 'satuan' => 'Bidang'],
            ['icon' => 'building-office-2', 'label' => 'Kecamatan Terdaftar', 'value' => $ringkasan['kecamatan'], 'satuan' => 'Kecamatan'],
        ];
    }
}; ?>

<div>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-linear-to-br from-primary-50 via-krem to-white">
        <x-ornamen-daun class="pointer-events-none absolute -start-6 -top-8 size-32 -scale-x-100 text-primary-100" />

        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 pb-28 pt-10 sm:px-6 lg:grid-cols-2 lg:gap-12 lg:px-8 lg:pb-32 lg:pt-16">
            <div>
                <p class="text-lg font-semibold text-primary-900 sm:text-xl">Selamat Datang di</p>
                <h1 class="mt-1 text-4xl font-extrabold leading-tight tracking-tight text-primary-700 text-balance sm:text-5xl lg:text-6xl">
                    {{ setting('hero_judul', 'JARIYAH BATANG HARI') }}
                </h1>
                <p class="mt-4 text-lg font-semibold text-primary-900 sm:text-xl">
                    {{ setting('hero_subjudul', 'Sistem Informasi Pengelolaan dan Monitoring Tanah Wakaf Kabupaten Batang Hari') }}
                </p>
                @if (filled(setting('hero_deskripsi')))
                    <p class="mt-3 max-w-xl leading-relaxed text-zinc-600">{{ setting('hero_deskripsi') }}</p>
                @endif

                <div class="mt-7 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">
                            <flux:icon.squares-2x2 class="size-4" /> Buka Dashboard
                        </a>
                        <a href="{{ route('peta') }}" wire:navigate class="btn-outline">Lihat Peta Wakaf</a>
                    @else
                        <a href="{{ route('register') }}" class="btn-primary">Daftar Sekarang</a>
                        <a href="{{ route('login') }}" class="btn-outline min-w-28">Login</a>
                    @endauth
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-lg lg:max-w-none">
                <p class="absolute end-2 top-0 z-10 max-w-44 -rotate-6 text-end text-xl leading-snug text-primary-800 sm:end-4 sm:max-w-52 sm:text-2xl"
                    style="font-family: 'Segoe Script', 'Brush Script MT', 'Lucida Handwriting', cursive;">
                    Wakaf untuk Masa Depan Umat
                </p>
                <x-publik.ilustrasi-masjid class="h-auto w-full drop-shadow-sm" />
            </div>
        </div>

        {{-- Gelombang bawah --}}
        <svg class="absolute inset-x-0 bottom-0 h-16 w-full text-white sm:h-20" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
            <path fill="#d6ecdb" d="M0 70c240-50 480-50 720-10s480 40 720-10v70H0Z" opacity=".8" />
            <path fill="currentColor" d="M0 90c260-40 520-30 760 0s460 20 680-10v40H0Z" />
        </svg>
    </section>

    {{-- Statistik --}}
    <section aria-labelledby="judul-statistik" class="relative z-10 mx-auto -mt-20 max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="judul-statistik" class="sr-only">Statistik Tanah Wakaf</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->statistik as $kartu)
                <x-stat-card :icon="$kartu['icon']" :label="$kartu['label']" :value="$kartu['value']" :satuan="$kartu['satuan']" :href="route('peta')" />
            @endforeach
        </div>
    </section>

    {{-- Banner persyaratan --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="kartu relative overflow-hidden bg-linear-to-r from-primary-50 to-white p-6 sm:p-8">
            <x-ornamen-daun class="pointer-events-none absolute -bottom-6 -end-4 size-28 text-primary-200/70" />
            <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <x-ikon-kotak icon="clipboard-document-check" class="hidden sm:flex" />
                    <div>
                        <h2 class="text-xl font-bold text-primary-900">Persyaratan Pengajuan Tanah Wakaf</h2>
                        <p class="mt-1 text-sm text-zinc-600">Siapkan dokumen yang dibutuhkan sebelum melakukan pendaftaran atau pengajuan data tanah wakaf.</p>
                    </div>
                </div>
                <a href="{{ route('persyaratan') }}" wire:navigate class="btn-primary shrink-0">
                    Lihat Semua Persyaratan <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
        </div>
    </section>
</div>
