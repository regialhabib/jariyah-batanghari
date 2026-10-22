<?php

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflow;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengajuan')] class extends Component {
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $jenis = '';

    public ?int $hapusId = null;

    public function updated(string $properti): void
    {
        if (in_array($properti, ['q', 'status', 'jenis'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status', 'jenis');
        $this->resetPage();
    }

    public function konfirmasiHapus(int $id): void
    {
        $pengajuan = Pengajuan::query()->forUser(auth()->user())->findOrFail($id);
        $this->authorize('delete', $pengajuan);

        $this->hapusId = $pengajuan->id;
        Flux::modal('hapus-pengajuan')->show();
    }

    public function hapus(): void
    {
        $pengajuan = Pengajuan::query()->forUser(auth()->user())->with('tanahWakaf', 'dokumens')->findOrFail($this->hapusId);
        $this->authorize('delete', $pengajuan);

        app(PengajuanWorkflow::class)->hapusDraf($pengajuan, auth()->user());

        $this->hapusId = null;
        Flux::modal('hapus-pengajuan')->close();
        Flux::toast(variant: 'success', text: 'Draf pengajuan berhasil dihapus.');
    }

    #[Computed]
    public function pengajuans()
    {
        $q = trim(mb_substr($this->q, 0, 100));

        return Pengajuan::query()
            ->forUser(auth()->user())
            ->with('tanahWakaf.desa.kecamatan')
            ->when(StatusPengajuan::tryFrom($this->status), fn (Builder $b, $s) => $b->where('status', $s->value))
            ->when(JenisPengajuan::tryFrom($this->jenis), fn (Builder $b, $j) => $b->where('jenis', $j->value))
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('nomor', 'like', "%{$q}%")
                ->orWhereHas('tanahWakaf', fn (Builder $t) => $t->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%"))))
            ->latest('updated_at')
            ->latest('id')
            ->paginate(10);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Pengajuan</flux:heading>
            <flux:text class="mt-1">Pantau status pengajuan AIW dan sertifikat wakaf Anda.</flux:text>
        </div>
        <flux:button :href="route('nazir.pengajuan.create')" wire:navigate variant="primary" icon="plus">Buat Pengajuan</flux:button>
    </div>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nomor atau nama tanah..." maxlength="100" aria-label="Cari pengajuan" />
        <flux:select wire:model.live="status" aria-label="Filter status">
            <flux:select.option value="">Semua Status</flux:select.option>
            @foreach (\App\Enums\StatusPengajuan::cases() as $kasus)
                <flux:select.option value="{{ $kasus->value }}">{{ $kasus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="jenis" aria-label="Filter jenis">
            <flux:select.option value="">Semua Jenis</flux:select.option>
            @foreach (\App\Enums\JenisPengajuan::cases() as $kasus)
                <flux:select.option value="{{ $kasus->value }}">{{ $kasus->labelSingkat() }}</flux:select.option>
            @endforeach
        </flux:select>
        <div>
            @if ($q !== '' || $status !== '' || $jenis !== '')
                <flux:button variant="ghost" icon="x-mark" wire:click="resetFilter">Reset filter</flux:button>
            @endif
        </div>
    </div>

    <div class="kartu overflow-hidden">
        @if ($this->pengajuans->isEmpty())
            <x-empty-state icon="clipboard-document-list"
                :title="($q !== '' || $status !== '' || $jenis !== '') ? 'Tidak ada pengajuan yang cocok' : 'Belum ada pengajuan'"
                description="Buat pengajuan Akta Ikrar Wakaf atau sertifikat wakaf untuk tanah yang Anda kelola.">
                <flux:button :href="route('nazir.pengajuan.create')" wire:navigate variant="primary" icon="plus" class="mt-2">Buat Pengajuan</flux:button>
            </x-empty-state>
        @else
            <div class="px-4 pb-4 sm:px-5">
                <flux:table :paginate="$this->pengajuans">
                    <flux:table.columns>
                        <flux:table.column>Nomor</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Tanah Wakaf</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->pengajuans as $p)
                            <flux:table.row :key="$p->id" @class(['bg-amber-50/60' => $p->status === \App\Enums\StatusPengajuan::PerluPerbaikan])>
                                <flux:table.cell class="font-medium text-primary-900">
                                    {{ $p->nomor ?? 'Draf' }}
                                </flux:table.cell>
                                <flux:table.cell>{{ $p->jenis->labelSingkat() }}</flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-medium text-primary-900">{{ $p->tanahWakaf?->nama }}</p>
                                    <p class="text-xs text-zinc-500">{{ $p->tanahWakaf?->desa?->nama }}, {{ $p->tanahWakaf?->desa?->kecamatan?->nama }}</p>
                                </flux:table.cell>
                                <flux:table.cell><x-badge-status :status="$p->status" /></flux:table.cell>
                                <flux:table.cell class="text-sm">
                                    @if ($p->diajukan_at)
                                        <p>Diajukan {{ $p->diajukan_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    @else
                                        <p>Dibuat {{ $p->created_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    @endif
                                    @if ($p->selesai_at)
                                        <p class="text-xs text-zinc-500">Selesai {{ $p->selesai_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" icon="eye" :href="route('nazir.pengajuan.show', $p)" wire:navigate>Detail</flux:button>
                                        @can('delete', $p)
                                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600!" wire:click="konfirmasiHapus({{ $p->id }})">Hapus</flux:button>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>

    <flux:modal name="hapus-pengajuan" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus draf pengajuan?</flux:heading>
            <flux:text>
                Draf beserta dokumen yang sudah diunggah akan dihapus permanen. Untuk draf AIW, data tanah yang
                dibuat bersama draf ini juga ikut dihapus.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="hapus">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
