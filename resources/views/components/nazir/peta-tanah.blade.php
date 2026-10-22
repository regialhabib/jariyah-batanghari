@props([
    // Koleksi TanahWakaf milik Nazir (sebaiknya dengan relasi desa.kecamatan, nazir, pengajuans).
    'tanahs',
    'tinggi' => 'h-96',
    'scrollZoom' => true,
    'legenda' => true,
])

@php
    // dataPublik() mengarah ke halaman publik yang 404 untuk tanah belum terbit, jadi tombol
    // "Lihat Detail" diarahkan ke pengajuan terbaru tanah tersebut (atau ke Data Wakaf Saya).
    $data = collect($tanahs)->map(function (\App\Models\TanahWakaf $tanah) {
        $pengajuan = $tanah->relationLoaded('pengajuans') ? $tanah->pengajuans->sortByDesc('id')->first() : null;

        return [
            ...$tanah->dataPublik(),
            'url' => $pengajuan ? route('nazir.pengajuan.show', $pengajuan) : route('nazir.tanah'),
        ];
    })->values()->all();
@endphp

<x-peta
    mode="data"
    :data="$data"
    :tinggi="$tinggi"
    :scroll-zoom="$scrollZoom"
    :legenda="$legenda"
    label="Peta tanah wakaf saya"
    wire:key="peta-nazir-{{ md5(json_encode(array_map(fn ($d) => [$d['kode'], $d['lat'], $d['lng'], $d['status']], $data))) }}"
/>
