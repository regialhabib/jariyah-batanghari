<?php

use App\Models\PanduanLangkah;
use App\Models\Unduhan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Panduan')] class extends Component {
    /**
     * @return Collection<int, PanduanLangkah>
     */
    #[Computed]
    public function langkahs(): Collection
    {
        return PanduanLangkah::query()->orderBy('urutan')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Unduhan>
     */
    #[Computed]
    public function unduhans(): Collection
    {
        return Unduhan::query()->orderBy('urutan')->orderBy('id')->get();
    }
}; ?>

<div>
    <x-page-header title="Panduan" icon="book-open" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Panduan']]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-primary-900">Panduan Pengurusan Tanah Wakaf</h2>
        <p class="mt-1 text-zinc-600">Berikut adalah langkah-langkah dan informasi yang dapat membantu Anda dalam pengurusan tanah wakaf.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Langkah --}}
            <section class="kartu p-5 sm:p-6 lg:col-span-2" aria-label="Langkah pengurusan">
                @if ($this->langkahs->isEmpty())
                    <x-empty-state icon="book-open" title="Panduan belum tersedia" />
                @else
                    <ol class="relative">
                        @foreach ($this->langkahs as $langkah)
                            <li wire:key="langkah-{{ $langkah->id }}" class="relative flex gap-4 pb-6 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute start-[1.1rem] top-10 bottom-0 w-0.5 bg-primary-100" aria-hidden="true"></span>
                                @endunless
                                <span class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white shadow-sm">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="pt-1">
                                    <h3 class="font-semibold text-primary-900">{{ $langkah->judul }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-zinc-600">{{ $langkah->deskripsi }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif

                <div class="mt-6 flex flex-wrap gap-3 border-t border-primary-100 pt-5">
                    <a href="{{ route('persyaratan') }}" wire:navigate class="btn-outline">
                        <flux:icon.clipboard-document-check class="size-4" /> Lihat Persyaratan
                    </a>
                    <a href="{{ route('register') }}" class="btn-primary">
                        <flux:icon.user-plus class="size-4" /> Daftar Akun Nazir
                    </a>
                </div>
            </section>

            {{-- Dokumen Penting --}}
            <section id="dokumen-penting" class="kartu scroll-mt-24 p-5 sm:p-6" aria-labelledby="judul-dokumen">
                <h2 id="judul-dokumen" class="text-lg font-semibold text-primary-900">Dokumen Penting</h2>

                @if ($this->unduhans->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">Belum ada dokumen.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($this->unduhans as $unduhan)
                            <li wire:key="unduhan-{{ $unduhan->id }}">
                                @if ($unduhan->tersedia())
                                    <a href="{{ $unduhan->url() }}"
                                        class="group flex items-center gap-3 rounded-xl p-2 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-primary-700">
                                        <x-ikon-kotak icon="document-text" size="sm" variant="soft" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-medium text-zinc-800 group-hover:text-primary-700">{{ $unduhan->judul }}</span>
                                            <span class="block text-xs text-zinc-500">{{ $unduhan->keterangan ?: 'Unduh di sini' }}</span>
                                        </span>
                                        <flux:icon.arrow-down-tray class="size-4 shrink-0 text-primary-700" />
                                    </a>
                                @else
                                    <div class="flex items-center gap-3 p-2">
                                        <x-ikon-kotak icon="document-text" size="sm" variant="soft" class="opacity-60" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-medium text-zinc-600">{{ $unduhan->judul }}</span>
                                            <span class="block text-xs text-zinc-400">Segera tersedia</span>
                                        </span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($this->unduhans->contains(fn ($u) => $u->tersedia()))
                        <a href="{{ route('unduhan.semua') }}" class="btn-primary mt-5 w-full">
                            <flux:icon.archive-box-arrow-down class="size-4" /> Unduh Semua Dokumen
                        </a>
                    @else
                        <p class="mt-5 rounded-lg bg-primary-50 px-3 py-2 text-center text-xs text-primary-800">Dokumen sedang disiapkan oleh petugas.</p>
                    @endif
                @endif
            </section>
        </div>
    </div>
</div>
