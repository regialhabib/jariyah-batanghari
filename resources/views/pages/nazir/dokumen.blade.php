<?php

use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Dokumen')] class extends Component {
    use WithPagination;

    #[Url]
    public string $q = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pengajuans()
    {
        $q = trim(mb_substr($this->q, 0, 100));

        return Pengajuan::query()
            ->forUser(auth()->user())
            ->whereHas('dokumens')
            ->with(['tanahWakaf', 'dokumens' => fn ($d) => $d->with('persyaratan')->orderBy('kategori')->orderBy('id')])
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('nomor', 'like', "%{$q}%")
                ->orWhereHas('tanahWakaf', fn (Builder $t) => $t->where('nama', 'like', "%{$q}%"))))
            ->latest('updated_at')
            ->latest('id')
            ->paginate(5);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Dokumen</flux:heading>
            <flux:text class="mt-1">Semua dokumen yang Anda unggah dan dokumen hasil (AIW/sertifikat), dikelompokkan per pengajuan.</flux:text>
        </div>
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nomor atau nama tanah..." maxlength="100" aria-label="Cari dokumen" />
        </div>
    </div>

    @if ($this->pengajuans->isEmpty())
        <div class="kartu">
            <x-empty-state icon="folder" :title="$q !== '' ? 'Tidak ada dokumen yang cocok' : 'Belum ada dokumen'"
                description="Dokumen persyaratan diunggah dari halaman detail pengajuan." />
        </div>
    @else
        @foreach ($this->pengajuans as $p)
            <section wire:key="grup-{{ $p->id }}" class="kartu overflow-hidden" aria-labelledby="grup-{{ $p->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-100 bg-primary-50/50 px-5 py-3">
                    <div class="min-w-0">
                        <h2 id="grup-{{ $p->id }}" class="font-semibold text-primary-900">{{ $p->nomor ?? 'Draf' }} · {{ $p->tanahWakaf?->nama }}</h2>
                        <p class="text-xs text-zinc-600">{{ $p->jenis->label() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-badge-status :status="$p->status" />
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('nazir.pengajuan.show', $p)" wire:navigate>Detail</flux:button>
                    </div>
                </div>
                <ul class="divide-y divide-primary-50">
                    @foreach ($p->dokumens as $dok)
                        <li wire:key="dok-{{ $dok->id }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <flux:icon :name="$dok->kategori === \App\Enums\KategoriDokumen::Persyaratan ? 'document-text' : 'document-check'" class="mt-0.5 size-5 shrink-0 text-primary-700" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-primary-900">{{ $dok->judul() }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $dok->nama_asli }} · {{ number_format($dok->ukuran / 1024, 0, ',', '.') }} KB · {{ $dok->created_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    @if (filled($dok->catatan))
                                        <p class="mt-1 text-xs text-red-700">Catatan: {{ $dok->catatan }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($dok->kategori === \App\Enums\KategoriDokumen::Persyaratan)
                                    <x-badge-status :status="$dok->status_validasi" />
                                @else
                                    <span class="rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-700">Dokumen Hasil</span>
                                @endif
                                <flux:button size="sm" variant="ghost" icon="eye" :href="$dok->url()" target="_blank" aria-label="Buka {{ $dok->judul() }}">Buka</flux:button>
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="$dok->url().'?unduh=1'" aria-label="Unduh {{ $dok->judul() }}">Unduh</flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <flux:pagination :paginator="$this->pengajuans" />
    @endif
</div>
