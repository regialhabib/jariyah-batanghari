<?php

use App\Enums\StatusSertifikat;
use App\Models\TanahWakaf;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Data Wakaf Saya')] class extends Component {
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    public function updated(string $properti): void
    {
        if (in_array($properti, ['q', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function tanahs()
    {
        $q = trim(mb_substr($this->q, 0, 100));

        return TanahWakaf::query()
            ->forUser(auth()->user())
            ->with(['desa.kecamatan', 'pengajuans' => fn ($p) => $p->latest('id')])
            ->when(StatusSertifikat::tryFrom($this->status), fn (Builder $b, $s) => $b->where('status_sertifikat', $s->value))
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%")
                ->orWhereHas('desa', fn (Builder $d) => $d->where('nama', 'like', "%{$q}%"))))
            ->latest('id')
            ->paginate(9);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Data Wakaf Saya</flux:heading>
            <flux:text class="mt-1">Tanah wakaf yang Anda kelola sebagai Nazir.</flux:text>
        </div>
        <flux:button :href="route('nazir.pengajuan.create', ['jenis' => 'aiw'])" wire:navigate variant="primary" icon="plus">Ajukan AIW Baru</flux:button>
    </div>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nama, kode, atau desa..." maxlength="100" aria-label="Cari tanah wakaf" />
        </div>
        <flux:select wire:model.live="status" aria-label="Filter status sertifikat">
            <flux:select.option value="">Semua Status Sertifikat</flux:select.option>
            @foreach (\App\Enums\StatusSertifikat::cases() as $kasus)
                <flux:select.option value="{{ $kasus->value }}">{{ $kasus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->tanahs->isEmpty())
        <div class="kartu">
            <x-empty-state icon="map-pin"
                :title="($q !== '' || $status !== '') ? 'Tidak ada tanah yang cocok' : 'Belum ada data tanah wakaf'"
                description="Data tanah tercatat saat Anda membuat pengajuan Akta Ikrar Wakaf, atau ketika didaftarkan oleh BPN atas nama Anda.">
                <flux:button :href="route('nazir.pengajuan.create', ['jenis' => 'aiw'])" wire:navigate variant="primary" icon="plus" class="mt-2">Ajukan AIW</flux:button>
            </x-empty-state>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->tanahs as $tanah)
                @php
                    $pengajuans = $tanah->pengajuans;
                    $aktif = $pengajuans->first(fn ($p) => $p->status->isAktif());
                    $drafSertifikat = $pengajuans->first(fn ($p) => $p->jenis->isSertifikat() && $p->status === \App\Enums\StatusPengajuan::Draf);
                    $bisaAjukan = filled($tanah->nomor_aiw) && $tanah->status_sertifikat !== \App\Enums\StatusSertifikat::Sudah
                        && ! $pengajuans->contains(fn ($p) => $p->jenis->isSertifikat() && $p->status->isAktif());
                    $terbaru = $pengajuans->first();
                @endphp
                <article wire:key="tanah-{{ $tanah->id }}" class="kartu flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-primary-700">{{ $tanah->jenis_penggunaan->label() }}</p>
                            <h2 class="mt-0.5 font-semibold text-primary-900">{{ $tanah->nama }}</h2>
                            <p class="text-xs text-zinc-500">{{ $tanah->kode }}</p>
                        </div>
                        <x-badge-status :status="$tanah->status_sertifikat" />
                    </div>

                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex gap-2"><dt class="w-28 shrink-0 text-zinc-500">Luas</dt><dd class="text-primary-900">{{ format_luas($tanah->luas_m2) }}</dd></div>
                        <div class="flex gap-2"><dt class="w-28 shrink-0 text-zinc-500">Lokasi</dt><dd class="text-primary-900">{{ $tanah->desa?->nama }}, {{ $tanah->desa?->kecamatan?->nama }}</dd></div>
                        <div class="flex gap-2"><dt class="w-28 shrink-0 text-zinc-500">Nomor AIW</dt><dd class="text-primary-900">{{ $tanah->nomor_aiw ?: 'Belum ada' }}</dd></div>
                        <div class="flex gap-2"><dt class="w-28 shrink-0 text-zinc-500">No. Sertifikat</dt><dd class="text-primary-900">{{ $tanah->nomor_sertifikat ?: '-' }}</dd></div>
                        <div class="flex gap-2">
                            <dt class="w-28 shrink-0 text-zinc-500">Publikasi</dt>
                            <dd>
                                @if ($tanah->is_published)
                                    <span class="inline-flex items-center gap-1 text-primary-700"><flux:icon.globe-alt class="size-4" /> Tampil di peta publik</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-zinc-500"><flux:icon.eye-slash class="size-4" /> Belum dipublikasikan</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($aktif)
                        <p class="mt-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">
                            Pengajuan {{ $aktif->jenis->labelSingkat() }} sedang diproses ({{ $aktif->status->label() }}).
                        </p>
                    @endif

                    <div class="mt-auto flex flex-wrap gap-2 pt-4">
                        @if ($bisaAjukan)
                            @if ($drafSertifikat)
                                <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('nazir.pengajuan.show', $drafSertifikat)" wire:navigate>Lanjutkan Draf Sertifikat</flux:button>
                            @else
                                <flux:dropdown>
                                    <flux:button size="sm" variant="primary" icon="shield-check" icon:trailing="chevron-down">Ajukan Sertifikat</flux:button>
                                    <flux:menu>
                                        <flux:menu.item :href="route('nazir.pengajuan.create', ['jenis' => \App\Enums\JenisPengajuan::SertifikatBersertifikat->value, 'tanah' => $tanah->id])" wire:navigate>
                                            Tanah sudah bersertifikat hak
                                        </flux:menu.item>
                                        <flux:menu.item :href="route('nazir.pengajuan.create', ['jenis' => \App\Enums\JenisPengajuan::SertifikatBelum->value, 'tanah' => $tanah->id])" wire:navigate>
                                            Tanah belum bersertifikat hak
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        @endif
                        @if ($terbaru)
                            <flux:button size="sm" variant="ghost" icon="clipboard-document-list" :href="route('nazir.pengajuan.show', $terbaru)" wire:navigate>Pengajuan Terakhir</flux:button>
                        @endif
                        @if ($tanah->is_published)
                            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('peta.show', $tanah->kode)" target="_blank">Halaman Publik</flux:button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <flux:pagination :paginator="$this->tanahs" />
    @endif
</div>
