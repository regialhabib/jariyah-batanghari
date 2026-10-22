@props([
    'title',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['flex w-full flex-col gap-1.5']) }}>
    @if ($icon)
        <x-ikon-kotak :icon="$icon" size="md" variant="soft" class="mb-2" />
    @endif

    <h1 class="text-2xl font-bold text-primary-900 sm:text-3xl">{{ $title }}</h1>

    @if ($description)
        <p class="text-sm leading-relaxed text-zinc-500">{{ $description }}</p>
    @endif
</div>
