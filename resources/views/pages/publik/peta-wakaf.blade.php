<?php

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Http\Controllers\PetaDataController;
use App\Models\Kecamatan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Peta Wakaf')] class extends Component {
    #[Url]
    public string $q = '';

    #[Url]
    public string $kecamatan = '';

    #[Url]
    public string $jenis = '';

    #[Url]
    public string $status = '';

    /**
     * Kirim filter ke peta (Alpine `petaWakaf` mendengarkan event `peta-filter`).
     */
    public function terapkan(): void
    {
        $this->q = trim(mb_substr($this->q, 0, 100));

        $this->dispatch('peta-filter', filter: $this->filter());
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'kecamatan', 'jenis', 'status');
        $this->terapkan();
    }

    /**
     * @return array{q: string, kecamatan: string, jenis: string, status: string}
     */
    protected function filter(): array
    {
        return [
            'q' => $this->q,
            'kecamatan' => $this->kecamatan,
            'jenis' => $this->jenis,
            'status' => $this->status,
        ];
    }

    /**
     * @return array{total: int, kecamatan: int, desa: int}
     */
    #[Computed]
    public function ringkasan(): array
    {
        return PetaDataController::ringkasan($this->filter());
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarKecamatan(): array
    {
        return Kecamatan::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }
}; ?>

<div>
    <x-page-header
        title="Peta Wakaf"
        icon="map"
        subtitle="Sebaran tanah wakaf di Kabupaten Batang Hari beserta status sertifikatnya."
        :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Peta Wakaf']]"
    />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Filter --}}
            <aside class="lg:col-span-3">
                <form wire:submit="terapkan" class="kartu space-y-4 p-5" aria-labelledby="judul-filter">
                    <h2 id="judul-filter" class="flex items-center gap-2 font-semibold text-primary-900">
                        <flux:icon.funnel class="size-5 text-primary-700" /> Filter Pencarian
                    </h2>

                    <flux:input wire:model="q" label="Cari" icon="magnifying-glass" placeholder="Nama, kode, atau desa..." maxlength="100" />

                    <flux:select wire:model="kecamatan" label="Kecamatan">
                        <flux:select.option value="">Semua Kecamatan</flux:select.option>
                        @foreach ($this->daftarKecamatan as $id => $nama)
                            <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="jenis" label="Jenis Penggunaan">
                        <flux:select.option value="">Semua Jenis</flux:select.option>
                        @foreach (JenisPenggunaan::opsi() as $nilai => $label)
                            <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="status" label="Status Sertifikat">
                        <flux:select.option value="">Semua Status</flux:select.option>
                        @foreach (StatusSertifikat::cases() as $kasus)
                            <flux:select.option value="{{ $kasus->value }}">{{ $kasus->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex gap-2 pt-1">
                        <flux:button type="submit" variant="primary" class="flex-1" icon="adjustments-horizontal">Terapkan</flux:button>
                        <flux:button type="button" variant="ghost" wire:click="resetFilter" aria-label="Atur ulang filter">Reset</flux:button>
                    </div>
                </form>
            </aside>

            {{-- Peta --}}
            <section class="lg:col-span-6" aria-label="Peta sebaran tanah wakaf">
                <x-peta mode="publik" :filter="$this->filter()" tinggi="h-[420px] sm:h-[520px] lg:h-[600px]" />
            </section>

            {{-- Informasi --}}
            <aside class="lg:col-span-3">
                <div class="kartu overflow-hidden">
                    <div class="relative flex h-36 items-center justify-center overflow-hidden bg-linear-to-br from-primary-700 to-primary-900">
                        <x-ornamen-daun class="absolute -end-6 -top-6 size-32 text-white/10" />
                        <x-ornamen-daun class="absolute -bottom-8 -start-6 size-28 rotate-180 text-white/10" />
                        <svg viewBox="0 0 120 80" class="relative h-24 text-white" fill="none" aria-hidden="true">
                            <path d="M8 18 40 8l40 10 32-10v54L80 72 40 62 8 72Z" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                            <path d="M40 8v54M80 18v54" stroke="currentColor" stroke-opacity=".5" stroke-width="2" />
                            <path d="M60 22c-7 0-12 5.3-12 11.8C48 43 60 54 60 54s12-11 12-20.2C72 27.3 67 22 60 22Z" fill="#8bc34a" stroke="#fff" stroke-width="2" />
                            <circle cx="60" cy="34" r="4" fill="#fff" />
                            <circle cx="24" cy="44" r="4" fill="#F59E0B" stroke="#fff" stroke-width="1.5" />
                            <circle cx="95" cy="40" r="4" fill="#3B82F6" stroke="#fff" stroke-width="1.5" />
                        </svg>
                    </div>

                    <div class="space-y-4 p-5">
                        <h2 class="flex items-center gap-2 font-semibold text-primary-900">
                            <flux:icon.information-circle class="size-5 text-primary-700" /> Informasi
                        </h2>
                        <p class="text-sm leading-relaxed text-zinc-600">
                            Peta menampilkan sebaran tanah wakaf di Kabupaten Batang Hari. Klik pada titik untuk melihat detail informasi.
                        </p>

                        <div class="rounded-xl bg-primary-50 p-4">
                            <p class="text-xs font-medium text-zinc-600">Total Tanah Wakaf</p>
                            <p class="mt-0.5 text-2xl font-bold text-primary-800">
                                {{ number_format($this->ringkasan['total'], 0, ',', '.') }} <span class="text-sm font-semibold">Bidang</span>
                            </p>
                        </div>

                        <div class="flex items-start gap-3">
                            <x-ikon-kotak icon="map-pin" size="sm" variant="soft" />
                            <div>
                                <p class="text-xs font-medium text-zinc-600">Tersebar di</p>
                                <p class="text-sm font-semibold text-primary-900">
                                    {{ $this->ringkasan['kecamatan'] }} Kecamatan, {{ $this->ringkasan['desa'] }} Desa/Kelurahan
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
