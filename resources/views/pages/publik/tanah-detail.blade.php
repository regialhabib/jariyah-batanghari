<?php

use App\Models\TanahWakaf;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::publik')] class extends Component {
    public TanahWakaf $tanah;

    public function mount(TanahWakaf $tanah): void
    {
        abort_unless($tanah->is_published, 404);
        $this->tanah = $tanah->loadMissing(['desa.kecamatan', 'nazir']);
    }

    public function render()
    {
        return $this->view()->title($this->tanah->nama);
    }
}; ?>

<div>
    @php
        $tanggal = fn ($tgl) => $tgl?->locale('id')->translatedFormat('j F Y');
        $rincian = [
            ['Kode', $tanah->kode, 'qr-code'],
            ['Jenis Penggunaan', $tanah->jenis_penggunaan->label(), 'building-library'],
            ['Luas', format_luas($tanah->luas_m2), 'arrows-pointing-out'],
            ['Desa/Kelurahan', $tanah->desa?->namaLengkap(), 'home-modern'],
            ['Kecamatan', $tanah->desa?->kecamatan?->nama, 'map'],
            ['Alamat', $tanah->alamat, 'map-pin'],
            ['Nazir', $tanah->namaNazirTampil(), 'user'],
            ['Nomor AIW', $tanah->nomor_aiw, 'document-text'],
            ['Tanggal AIW', $tanggal($tanah->tanggal_aiw), 'calendar'],
        ];
        if ($tanah->tanggal_sertifikat) {
            $rincian[] = ['Tanggal Sertifikat', $tanggal($tanah->tanggal_sertifikat), 'check-badge'];
        }
    @endphp

    <x-page-header :title="$tanah->nama" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Peta Wakaf', 'url' => route('peta')], ['label' => $tanah->nama]]" />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <a href="{{ route('peta') }}" wire:navigate class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:text-primary-800">
            <flux:icon.arrow-left class="size-4" /> Kembali ke Peta Wakaf
        </a>

        <div class="grid gap-6 lg:grid-cols-5">
            <article class="kartu overflow-hidden lg:col-span-3">
                @if ($tanah->fotoUrl())
                    <img src="{{ $tanah->fotoUrl() }}" alt="Foto {{ $tanah->nama }}" class="h-56 w-full object-cover sm:h-72">
                @endif

                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-primary-700">{{ $tanah->jenis_penggunaan->label() }}</p>
                            <h2 class="mt-1 text-xl font-bold text-primary-900">{{ $tanah->nama }}</h2>
                        </div>
                        <x-badge-status :status="$tanah->status_sertifikat" />
                    </div>

                    <dl class="mt-5 divide-y divide-zinc-100 border-t border-zinc-100">
                        @foreach ($rincian as [$label, $nilai, $ikon])
                            <div class="grid grid-cols-[auto_1fr] items-start gap-3 py-3 sm:grid-cols-[12rem_1fr]">
                                <dt class="flex items-center gap-2 text-sm text-zinc-500">
                                    <flux:icon :name="$ikon" class="size-4 shrink-0 text-primary-600" /> {{ $label }}
                                </dt>
                                <dd class="text-sm font-medium text-zinc-800">{{ filled($nilai) ? $nilai : '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </article>

            <aside class="space-y-4 lg:col-span-2">
                <x-peta mode="data" :data="[$tanah->dataPublik()]" :tombol-detail="false" :scroll-zoom="false" tinggi="h-80 lg:h-96" label="Lokasi {{ $tanah->nama }}" />

                <div class="kartu p-4 text-sm text-zinc-600">
                    <p class="flex items-center gap-2 font-medium text-zinc-800">
                        <flux:icon.map-pin class="size-4 text-primary-700" /> Koordinat
                    </p>
                    <p class="mt-1 font-mono">{{ number_format((float) $tanah->latitude, 6, '.', '') }}, {{ number_format((float) $tanah->longitude, 6, '.', '') }}</p>
                    <a href="https://www.google.com/maps?q={{ (float) $tanah->latitude }},{{ (float) $tanah->longitude }}" target="_blank" rel="noopener"
                        class="mt-2 inline-flex items-center gap-1 font-medium text-primary-700 hover:underline">
                        Buka di Google Maps <flux:icon.arrow-top-right-on-square class="size-3.5" />
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>
