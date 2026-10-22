<?php

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::publik')] class extends Component {
    public Artikel $artikel;

    public function mount(Artikel $artikel): void
    {
        abort_unless($artikel->is_published, 404);
        $this->artikel = $artikel;
    }

    /**
     * @return Collection<int, Artikel>
     */
    #[Computed]
    public function lainnya(): Collection
    {
        return Artikel::query()->published()
            ->whereKeyNot($this->artikel->id)
            ->get(['id', 'judul', 'slug', 'ikon', 'ringkasan']);
    }

    public function render()
    {
        return $this->view()->title($this->artikel->judul);
    }
}; ?>

<div>
    <x-page-header :title="$artikel->judul" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Informasi Wakaf', 'url' => route('informasi.index')], ['label' => $artikel->judul]]" />

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <article class="kartu p-6 sm:p-8 lg:col-span-2">
            <div class="flex items-start gap-4">
                <x-ikon-kotak :icon="$artikel->ikon ?: 'book-open'" />
                <p class="text-zinc-600 italic">{{ $artikel->ringkasan }}</p>
            </div>

            <flux:separator class="my-6" />

            {{-- Isi sudah disanitasi saat disimpan (Artikel::setIsiAttribute). --}}
            <div class="konten-artikel">
                {!! $artikel->isi !!}
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('informasi.index') }}" wire:navigate class="btn-outline">
                    <flux:icon.arrow-left class="size-4" /> Kembali ke Informasi Wakaf
                </a>
                <a href="{{ route('persyaratan') }}" wire:navigate class="btn-primary">
                    Lihat Persyaratan <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
        </article>

        <aside aria-labelledby="judul-artikel-lain">
            <div class="kartu p-5 lg:sticky lg:top-24">
                <h2 id="judul-artikel-lain" class="font-semibold text-primary-900">Artikel Lainnya</h2>
                @if ($this->lainnya->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">Belum ada artikel lain.</p>
                @else
                    <ul class="mt-3 divide-y divide-primary-100">
                        @foreach ($this->lainnya as $lain)
                            <li wire:key="lain-{{ $lain->id }}">
                                <a href="{{ route('informasi.show', $lain->slug) }}" wire:navigate
                                    class="flex items-center gap-3 rounded-lg py-3 text-sm text-zinc-700 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-primary-700">
                                    <x-ikon-kotak :icon="$lain->ikon ?: 'book-open'" size="sm" variant="soft" />
                                    <span class="font-medium">{{ $lain->judul }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </aside>
    </div>
</div>
