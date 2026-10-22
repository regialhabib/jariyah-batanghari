@props([
    'icon',
    'label',
    'value',
    'satuan' => null,
    'href' => null,
])

{{-- Kartu statistik: ikon kotak hijau, label kecil, angka besar, satuan (mis. "Bidang"). --}}
<div {{ $attributes->class(['kartu relative flex items-center gap-4 p-5']) }}>
    <x-ikon-kotak :icon="$icon" />

    <div class="min-w-0">
        <p class="truncate text-sm text-zinc-600">{{ $label }}</p>
        <p class="text-2xl font-bold text-primary-900">{{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}</p>
        @if ($satuan)
            <p class="text-xs text-zinc-500">{{ $satuan }}</p>
        @endif
    </div>

    @if ($href)
        <a href="{{ $href }}" class="absolute inset-0" wire:navigate><span class="sr-only">{{ $label }}</span></a>
    @endif
</div>
