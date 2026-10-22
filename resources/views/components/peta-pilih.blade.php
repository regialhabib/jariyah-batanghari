@props([
    // Nama properti Livewire (boleh bertitik, mis. 'form.latitude') yang di-entangle dengan peta.
    'lat' => 'latitude',
    'lng' => 'longitude',
    // Nama properti Livewire untuk polygon GeoJSON (array|null). null = alat gambar polygon tidak ditampilkan.
    'batas' => null,
    // true = setiap perubahan langsung dikirim ke server (wire:model.live); default ditunda sampai aksi berikutnya.
    'live' => false,
    'tinggi' => 'h-80',
    'pusat' => null,
    'zoom' => null,
    'petunjuk' => true,
])

@php
    $opsi = array_filter([
        'pusat' => $pusat,
        'zoom' => $zoom,
        'cssGeoman' => $batas ? asset('vendor/leaflet-geoman/leaflet-geoman.css') : null,
    ], fn ($v) => $v !== null);

    $live = $live ? 'true' : 'false';
    $xData = 'petaWakaf({ ...'.\Illuminate\Support\Js::from($opsi).', mode: \'pilih\''
        .', lat: $wire.entangle('.\Illuminate\Support\Js::from($lat).', '.$live.')'
        .', lng: $wire.entangle('.\Illuminate\Support\Js::from($lng).', '.$live.')'
        .($batas ? ', batas: $wire.entangle('.\Illuminate\Support\Js::from($batas).', '.$live.')' : '')
        .' })';
@endphp

@if ($batas)
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet-geoman/leaflet-geoman.css') }}">
    @endpush
@endif

{{--
    Pemilih lokasi untuk form Livewire (Alpine `petaWakaf` mode pilih). Kontrak: docs/komponen-peta.md
    <x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" />
--}}
<div wire:ignore x-data="{{ $xData }}" {{ $attributes->class(['space-y-2']) }}>
    <div class="relative isolate overflow-hidden rounded-xl border border-zinc-200 bg-primary-50 {{ $tinggi }}">
        <div x-ref="peta" class="absolute inset-0 z-0" role="application" aria-label="Peta pemilih lokasi tanah wakaf"></div>
    </div>

    @if ($petunjuk)
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-zinc-600">
            <p>
                Klik peta atau geser penanda untuk menentukan titik lokasi.
                @if ($batas)
                    Gunakan alat di kiri peta untuk menggambar, mengubah, atau menghapus batas bidang.
                @endif
            </p>
            <p class="font-mono text-zinc-700" x-show="lat !== null && lat !== '' && lat !== undefined" x-cloak>
                <span x-text="Number(lat).toFixed(6)"></span>, <span x-text="Number(lng).toFixed(6)"></span>
                <button type="button" class="ms-2 font-sans font-medium text-red-600 hover:underline" x-on:click="hapusTitik()">Hapus titik</button>
            </p>
        </div>
    @endif
</div>
