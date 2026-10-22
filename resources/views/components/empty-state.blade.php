@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-2 px-6 py-12 text-center']) }}>
    <x-ikon-kotak :icon="$icon" variant="soft" />
    <p class="font-semibold text-primary-900">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-zinc-600">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
