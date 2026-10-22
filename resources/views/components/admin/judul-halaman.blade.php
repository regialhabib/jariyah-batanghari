@props([
    'judul',
    'deskripsi' => null,
    // URL tautan "kembali" di atas judul (untuk halaman form/detail).
    'kembali' => null,
    'labelKembali' => 'Kembali',
    // Teks kecil di atas judul, mis. nama kelompok menu.
    'eyebrow' => null,
])

{{--
    Judul halaman bersama untuk semua halaman admin: tautan kembali / eyebrow, judul, keterangan,
    dan tombol aksi di kanan (isi slot). Keterangan yang berisi markup bisa diisi lewat slot "keterangan".
--}}
<header {{ $attributes->class(['flex flex-wrap items-end justify-between gap-x-4 gap-y-3']) }}>
    <div class="min-w-0">
        @if ($kembali)
            <a href="{{ $kembali }}" wire:navigate class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-zinc-500 transition hover:text-primary-700">
                <flux:icon.arrow-left variant="micro" class="size-4" /> {{ $labelKembali }}
            </a>
        @endif
        @if ($eyebrow)
            <p class="text-sm font-medium text-primary-700">{{ $eyebrow }}</p>
        @endif

        <flux:heading size="xl" level="1">{{ $judul }}</flux:heading>

        @if (isset($keterangan) && $keterangan->hasActualContent())
            <flux:text class="mt-1">{{ $keterangan }}</flux:text>
        @elseif ($deskripsi)
            <flux:text class="mt-1">{{ $deskripsi }}</flux:text>
        @endif
    </div>

    @if ($slot->hasActualContent())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</header>
