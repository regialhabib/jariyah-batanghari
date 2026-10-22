@props([
    'title' => null,
    'judul' => 'Selamat Datang di Jariyah Batang Hari',
    'deskripsi' => 'Silakan login untuk mengakses sistem sesuai dengan akun Anda.',
    'ilustrasi' => 'lanskap',
    'lebar' => false,
])

{{-- Semua halaman auth memakai layout dua kolom hijau (mockup H7/H8). --}}
<x-layouts::auth.split
    :title="$title"
    :judul="$judul"
    :deskripsi="$deskripsi"
    :ilustrasi="$ilustrasi"
    :lebar="$lebar"
>
    {{ $slot }}
</x-layouts::auth.split>
