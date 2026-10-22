@props([
    'judul',
    // Nama rute halaman ini (mis. 'admin.konten.artikel'). Tidak memakai request()->routeIs()
    // karena saat aksi Livewire, rute request adalah endpoint update Livewire.
    'aktif',
    'deskripsi' => null,
])

{{-- Judul halaman + sub-navigasi bersama untuk keenam halaman Kelola Konten. --}}
@php
    $menu = [
        ['Artikel', 'newspaper', 'admin.konten.artikel'],
        ['Persyaratan', 'clipboard-document-check', 'admin.konten.persyaratan'],
        ['Panduan', 'map', 'admin.konten.panduan'],
        ['FAQ', 'question-mark-circle', 'admin.konten.faq'],
        ['Unduhan', 'arrow-down-tray', 'admin.konten.unduhan'],
        ['Pengaturan', 'cog-6-tooth', 'admin.konten.pengaturan'],
    ];
@endphp

<div {{ $attributes->class(['space-y-4']) }}>
    <x-admin.judul-halaman :judul="$judul" :deskripsi="$deskripsi" eyebrow="Kelola Konten">
        {{ $slot }}
    </x-admin.judul-halaman>

    <nav aria-label="Sub-menu Kelola Konten" class="-mx-1 overflow-x-auto">
        <ul class="flex min-w-max gap-1 border-b border-primary-100 px-1">
            @foreach ($menu as [$label, $ikon, $rute])
                <li>
                    <a href="{{ route($rute) }}" wire:navigate @if ($aktif === $rute) aria-current="page" @endif @class([
                        '-mb-px flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition',
                        'border-primary-700 text-primary-800' => $aktif === $rute,
                        'border-transparent text-zinc-600 hover:border-primary-200 hover:text-primary-700' => $aktif !== $rute,
                    ])>
                        <flux:icon :name="$ikon" variant="micro" class="size-4" />
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
