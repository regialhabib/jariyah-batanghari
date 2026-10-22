<?php

use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sertifikat')] class extends Component {
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $kecamatan = '';

    public function mount(): void
    {
        abort_unless($this->pengguna()->can('sertifikat.catat-terbit'), 403);
    }

    protected function pengguna(): User
    {
        return auth()->user();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * Pengajuan sertifikat yang sudah diverifikasi dan menunggu dicatat terbit.
     *
     * @return Collection<int, Pengajuan>
     */
    #[Computed]
    public function menunggu(): Collection
    {
        $user = $this->pengguna();

        return Pengajuan::query()->forUser($user)
            ->where('status', StatusPengajuan::Terverifikasi->value)
            ->with(['tanahWakaf.desa.kecamatan', 'nazir'])
            ->oldest('updated_at')
            ->get()
            ->filter(fn (Pengajuan $p) => $user->can('periksa', $p))
            ->values();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarKecamatan(): array
    {
        return Kecamatan::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    #[Computed]
    public function tanahs(): LengthAwarePaginator
    {
        $q = trim(mb_substr($this->q, 0, 100));

        return TanahWakaf::query()->forUser($this->pengguna())
            ->where('status_sertifikat', StatusSertifikat::Sudah->value)
            ->with(['desa.kecamatan', 'nazir'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%")
                ->orWhere('nomor_sertifikat', 'like', "%{$q}%")))
            ->when((int) $this->kecamatan, fn (Builder $query, int $id) => $query->whereHas('desa', fn (Builder $d) => $d->where('kecamatan_id', $id)))
            ->orderByDesc('tanggal_sertifikat')
            ->orderByDesc('id')
            ->paginate(10);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Sertifikat</flux:heading>
        <flux:text class="mt-1">Pencatatan sertifikat wakaf yang telah terbit.</flux:text>
    </div>

    <section class="kartu p-5" aria-labelledby="judul-menunggu">
        <div class="flex items-center gap-2">
            <h2 id="judul-menunggu" class="font-semibold text-primary-900">Menunggu Penerbitan</h2>
            <flux:badge size="sm" color="teal">{{ $this->menunggu->count() }}</flux:badge>
        </div>

        @if ($this->menunggu->isEmpty())
            <x-empty-state icon="check-circle" title="Tidak ada pengajuan yang menunggu" description="Pengajuan sertifikat yang sudah terverifikasi akan muncul di sini." />
        @else
            <ul class="mt-3 divide-y divide-zinc-100">
                @foreach ($this->menunggu as $p)
                    <li wire:key="menunggu-{{ $p->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-800">{{ $p->tanahWakaf->nama }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ $p->nomor }} · {{ $p->jenis->labelSingkat() }} · {{ $p->nazir->name }} · {{ $p->tanahWakaf->desa?->kecamatan?->nama }}
                            </p>
                        </div>
                        <flux:button size="sm" variant="primary" icon="shield-check" :href="route('admin.pengajuan.show', $p)" wire:navigate>Catat Terbit</flux:button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="kartu p-5" aria-labelledby="judul-terbit">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="judul-terbit" class="font-semibold text-primary-900">Tanah Wakaf Bersertifikat</h2>
            <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nama, kode, atau nomor..." maxlength="100" aria-label="Cari" />
                <flux:select wire:model.live="kecamatan" aria-label="Kecamatan">
                    <flux:select.option value="">Semua Kecamatan</flux:select.option>
                    @foreach ($this->daftarKecamatan as $id => $nama)
                        <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if ($this->tanahs->isEmpty())
            <x-empty-state icon="shield-check" title="Belum ada tanah bersertifikat" description="Tidak ada data yang sesuai dengan pencarian." />
        @else
            <div class="mt-4 overflow-x-auto">
                <flux:table :paginate="$this->tanahs">
                    <flux:table.columns>
                        <flux:table.column>Kode</flux:table.column>
                        <flux:table.column>Nama Tanah Wakaf</flux:table.column>
                        <flux:table.column>Lokasi</flux:table.column>
                        <flux:table.column>Nomor Sertifikat</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->tanahs as $tanah)
                            <flux:table.row :key="$tanah->id">
                                <flux:table.cell class="font-mono text-xs">{{ $tanah->kode }}</flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-medium text-zinc-800">{{ $tanah->nama }}</p>
                                    <p class="text-xs text-zinc-500">Nazir: {{ $tanah->namaNazirTampil() ?? '-' }}</p>
                                </flux:table.cell>
                                <flux:table.cell>{{ $tanah->desa?->nama }}, {{ $tanah->desa?->kecamatan?->nama }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $tanah->nomor_sertifikat ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $tanah->tanggal_sertifikat?->locale('id')->translatedFormat('j M Y') ?? '-' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    @can('update', $tanah)
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.tanah.edit', $tanah)" wire:navigate aria-label="Ubah {{ $tanah->nama }}" />
                                    @endcan
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </section>
</div>
