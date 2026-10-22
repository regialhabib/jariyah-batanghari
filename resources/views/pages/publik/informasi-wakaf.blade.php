<?php

use App\Models\Artikel;
use App\Models\Unduhan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Informasi Wakaf')] class extends Component {
    /**
     * @return Collection<int, Artikel>
     */
    #[Computed]
    public function artikels(): Collection
    {
        return Artikel::query()->published()->get(['id', 'judul', 'slug', 'ikon', 'ringkasan']);
    }

    /**
     * Brosur wakaf dari Dokumen Penting (jika sudah diunggah admin).
     */
    #[Computed]
    public function brosur(): ?Unduhan
    {
        return Unduhan::query()
            ->where('judul', 'like', '%brosur%')
            ->whereNotNull('file')->where('file', '!=', '')
            ->orderBy('urutan')
            ->first();
    }
}; ?>

<div>
    {{-- Banner bergambar --}}
    <section class="relative overflow-hidden border-b border-primary-100 bg-linear-to-r from-primary-50 via-krem to-primary-50">
        <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-8 sm:px-6 lg:px-8">
            <div>
                <nav aria-label="Breadcrumb">
                    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-600">
                        <li class="flex items-center gap-1.5">
                            <flux:icon.home variant="micro" class="text-primary-700" />
                            <a href="{{ route('beranda') }}" wire:navigate class="hover:text-primary-700">Beranda</a>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <flux:icon.chevron-right class="size-3.5 text-zinc-400" />
                            <span aria-current="page" class="text-primary-800">Informasi Wakaf</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-3xl font-bold text-primary-900 sm:text-4xl">Informasi Wakaf</h1>
                <p class="mt-2 max-w-md text-zinc-600">Kenali wakaf dan pahami pengelolaannya sebelum melakukan pengajuan.</p>
            </div>
            <x-publik.ilustrasi-masjid class="hidden h-40 w-auto shrink-0 md:block lg:h-48" />
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-10 px-4 py-10 sm:px-6 lg:px-8">
        {{-- Grid artikel + kutipan --}}
        <section aria-label="Daftar artikel informasi wakaf" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->artikels as $artikel)
                <article wire:key="artikel-{{ $artikel->id }}" class="kartu group relative flex flex-col p-5 transition hover:-translate-y-0.5 hover:border-primary-300">
                    <x-ikon-kotak :icon="$artikel->ikon ?: 'book-open'" />
                    <h2 class="mt-4 font-semibold text-primary-900">{{ $artikel->judul }}</h2>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600">{{ $artikel->ringkasan }}</p>
                    <a href="{{ route('informasi.show', $artikel->slug) }}" wire:navigate
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 after:absolute after:inset-0 after:rounded-2xl group-hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-primary-700">
                        Selengkapnya
                        <span class="sr-only">tentang {{ $artikel->judul }}</span>
                        <span class="flex size-5 items-center justify-center rounded-full bg-primary-700 text-white">
                            <flux:icon.arrow-right variant="micro" class="size-3.5" />
                        </span>
                    </a>
                </article>
            @endforeach

            <figure class="kartu relative flex flex-col justify-center overflow-hidden bg-primary-50 p-6">
                <x-ornamen-daun class="pointer-events-none absolute -bottom-4 -end-3 size-24 text-primary-200" />
                <span class="text-5xl font-bold leading-none text-primary-700" aria-hidden="true">&ldquo;</span>
                <blockquote class="relative text-lg italic leading-snug text-primary-900">
                    Wakaf bukan hanya tentang melepaskan harta, tetapi tentang menanam kebaikan yang terus tumbuh untuk generasi mendatang.
                </blockquote>
                <span class="mt-4 block h-0.5 w-12 bg-primary-600" aria-hidden="true"></span>
            </figure>
        </section>

        @if ($this->artikels->isEmpty())
            <x-empty-state icon="book-open" title="Belum ada artikel" description="Artikel informasi wakaf akan segera ditambahkan." />
        @endif

        {{-- CTA --}}
        <section class="kartu relative overflow-hidden bg-linear-to-r from-primary-50 to-white p-6 sm:p-8">
            <x-ornamen-daun class="pointer-events-none absolute -bottom-8 -start-6 size-32 -scale-x-100 text-primary-200/70" />
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="lg:ps-20">
                    <h2 class="text-xl font-bold text-primary-900">Mari bersama wujudkan pengelolaan wakaf yang amanah dan bermanfaat bagi umat.</h2>
                    <p class="mt-2 text-sm text-zinc-600">Dengan memahami informasi wakaf, kita dapat berkontribusi dalam menjaga dan mengembangkan aset wakaf di Kabupaten Batang Hari.</p>
                </div>
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    @if ($this->brosur)
                        <a href="{{ $this->brosur->url() }}" class="btn-outline">
                            <flux:icon.document-arrow-down class="size-4" /> Unduh Brosur Wakaf
                        </a>
                    @else
                        <a href="{{ route('panduan') }}#dokumen-penting" wire:navigate class="btn-outline">
                            <flux:icon.document-arrow-down class="size-4" /> Unduh Brosur Wakaf
                        </a>
                    @endif
                    <a href="{{ route('panduan') }}" wire:navigate class="btn-primary">
                        <flux:icon.book-open class="size-4" /> Lihat Panduan Wakaf
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>
