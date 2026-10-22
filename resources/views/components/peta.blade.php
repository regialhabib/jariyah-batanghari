@props([
    // publik = ambil GeoJSON dari `url` (default route('peta.data')) | data = tampilkan array `data`
    'mode' => 'publik',
    // Mode data: array TanahWakaf::dataPublik(), mis. $tanahs->map->dataPublik()->all()
    'data' => [],
    'url' => null,
    // Filter awal mode publik: ['q' => '', 'kecamatan' => '', 'jenis' => '', 'status' => '']
    'filter' => [],
    // Event window yang memicu muat ulang dengan filter baru (detail: { filter: {...} })
    'event' => 'peta-filter',
    'tinggi' => 'h-96',
    'legenda' => true,
    'scrollZoom' => true,
    'tombolDetail' => true,
    'pusat' => null,
    'zoom' => null,
    'label' => 'Peta sebaran tanah wakaf',
])

@php
    $opsi = array_filter([
        'mode' => $mode,
        'url' => $mode === 'publik' ? ($url ?? route('peta.data')) : null,
        'filter' => $mode === 'publik' ? (object) array_filter($filter, fn ($v) => filled($v)) : null,
        'event' => $event,
        'data' => $mode === 'data' ? array_values(collect($data)->all()) : null,
        'legenda' => (bool) $legenda,
        'scrollZoom' => (bool) $scrollZoom,
        'tombolDetail' => (bool) $tombolDetail,
        'pusat' => $pusat,
        'zoom' => $zoom,
        // Garis batas kabupaten, aktif otomatis jika file GeoJSON-nya tersedia.
        'batasWilayah' => file_exists(public_path('geo/batang-hari.geojson')) ? asset('geo/batang-hari.geojson') : null,
    ], fn ($v) => $v !== null);
@endphp

{{-- Peta tanah wakaf (Alpine `petaWakaf`, resources/js/peta.js). Kontrak: docs/komponen-peta.md --}}
<div
    wire:ignore
    x-data="petaWakaf(@js($opsi))"
    {{ $attributes->class(['relative isolate overflow-hidden rounded-2xl border border-primary-100 bg-primary-50', $tinggi]) }}
>
    <div x-ref="peta" class="absolute inset-0 z-0" role="region" aria-label="{{ $label }}"></div>

    {{-- Memuat --}}
    <div x-show="memuat" x-cloak class="pointer-events-none absolute inset-x-0 top-3 z-[1000] flex justify-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-primary-800 shadow">
            <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" /><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" /></svg>
            Memuat data peta…
        </span>
    </div>

    {{-- Kosong --}}
    <div x-show="! memuat && ! galat && jumlah === 0" x-cloak class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="pointer-events-auto max-w-xs rounded-xl bg-white/95 px-4 py-3 text-center shadow">
            <p class="text-sm font-semibold text-primary-900">Tidak ada tanah wakaf</p>
            <p class="mt-0.5 text-xs text-zinc-600">Tidak ada data yang sesuai dengan filter yang dipilih.</p>
        </div>
    </div>

    {{-- Galat --}}
    <div x-show="galat" x-cloak class="absolute inset-x-0 top-3 z-[1000] flex justify-center px-4">
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-2 text-xs text-red-700 shadow" role="alert">
            <span x-text="galat"></span>
            <button type="button" class="font-semibold text-primary-700 underline" x-on:click="muat()">Coba lagi</button>
        </div>
    </div>
</div>
