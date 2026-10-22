@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    // [['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Halaman ini']]
    'breadcrumb' => [],
])

{{-- Banner judul halaman publik: judul besar + breadcrumb, latar hijau muda dengan ornamen daun. --}}
<section {{ $attributes->class(['relative overflow-hidden border-b border-primary-100 bg-linear-to-r from-primary-50 via-krem to-primary-50']) }}>
    <x-ornamen-daun class="pointer-events-none absolute -end-4 -top-6 h-32 w-32 text-primary-200/70" />

    <div class="relative mx-auto flex max-w-7xl items-center gap-4 px-4 py-8 sm:px-6 lg:px-8">
        @if ($icon)
            <x-ikon-kotak :icon="$icon" class="hidden sm:flex" />
        @endif

        <div>
            <h1 class="text-2xl font-bold text-primary-900 sm:text-3xl">{{ $title }}</h1>

            @if ($breadcrumb)
                <nav aria-label="Breadcrumb" class="mt-1.5">
                    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-600">
                        @foreach ($breadcrumb as $item)
                            <li class="flex items-center gap-1.5">
                                @if (! $loop->first)
                                    <flux:icon.chevron-right class="size-3.5 text-zinc-400" />
                                @endif
                                @if (! empty($item['url']) && ! $loop->last)
                                    <a href="{{ $item['url'] }}" class="hover:text-primary-700" wire:navigate>{{ $item['label'] }}</a>
                                @else
                                    <span @if ($loop->last) aria-current="page" class="text-primary-800" @endif>{{ $item['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            @if ($subtitle)
                <p class="mt-2 max-w-2xl text-sm text-zinc-600">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</section>
