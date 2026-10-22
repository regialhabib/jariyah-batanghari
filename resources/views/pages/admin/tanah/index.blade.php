<?php

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Data Wakaf')] class extends Component {
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $kecamatan = '';

    #[Url]
    public string $desa = '';

    #[Url]
    public string $jenis = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $terbit = '';

    public ?int $detailId = null;

    public ?int $hapusId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', TanahWakaf::class);
    }

    protected function pengguna(): User
    {
        return auth()->user();
    }

    public function updated(string $properti): void
    {
        if ($properti === 'kecamatan') {
            $this->desa = '';
        }

        if (in_array($properti, ['q', 'kecamatan', 'desa', 'jenis', 'status', 'terbit'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'kecamatan', 'desa', 'jenis', 'status', 'terbit');
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarKecamatan(): array
    {
        $user = $this->pengguna();

        return Kecamatan::query()->orderBy('nama')
            ->when($user->isKua(), fn ($q) => $q->whereKey($user->kecamatan_id))
            ->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarDesa(): array
    {
        $kecamatanId = (int) $this->kecamatan;
        if (! $kecamatanId || ! array_key_exists($kecamatanId, $this->daftarKecamatan)) {
            return [];
        }

        return Desa::query()->where('kecamatan_id', $kecamatanId)->orderBy('nama')->pluck('nama', 'id')->all();
    }

    #[Computed]
    public function tanahs(): LengthAwarePaginator
    {
        $q = trim(mb_substr($this->q, 0, 100));

        return TanahWakaf::query()->forUser($this->pengguna())
            ->with(['desa.kecamatan', 'nazir'])
            ->withCount('pengajuans')
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%")
                ->orWhere('wakif_nama', 'like', "%{$q}%")))
            ->when((int) $this->kecamatan, fn (Builder $query, int $id) => $query->whereHas('desa', fn (Builder $d) => $d->where('kecamatan_id', $id)))
            ->when((int) $this->desa, fn (Builder $query, int $id) => $query->where('desa_id', $id))
            ->when(JenisPenggunaan::tryFrom($this->jenis), fn (Builder $query, JenisPenggunaan $j) => $query->where('jenis_penggunaan', $j->value))
            ->when(StatusSertifikat::tryFrom($this->status), fn (Builder $query, StatusSertifikat $s) => $query->where('status_sertifikat', $s->value))
            ->when(in_array($this->terbit, ['1', '0'], true), fn (Builder $query) => $query->where('is_published', $this->terbit === '1'))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function detail(): ?TanahWakaf
    {
        if (! $this->detailId) {
            return null;
        }

        return TanahWakaf::query()->forUser($this->pengguna())->with(['desa.kecamatan', 'nazir'])->find($this->detailId);
    }

    public function lihat(int $id): void
    {
        $tanah = TanahWakaf::query()->forUser($this->pengguna())->findOrFail($id);
        $this->authorize('view', $tanah);

        $this->detailId = $tanah->id;
        unset($this->detail);
        Flux::modal('detail-tanah')->show();
    }

    public function konfirmasiHapus(int $id): void
    {
        $tanah = TanahWakaf::query()->forUser($this->pengguna())->findOrFail($id);
        $this->authorize('delete', $tanah);

        $this->hapusId = $tanah->id;
        Flux::modal('hapus-tanah')->show();
    }

    public function hapus(): void
    {
        $tanah = TanahWakaf::query()->forUser($this->pengguna())->findOrFail((int) $this->hapusId);
        $this->authorize('delete', $tanah);

        if ($tanah->foto) {
            Storage::disk('public')->delete($tanah->foto);
        }
        $tanah->delete();

        $this->hapusId = null;
        Flux::modal('hapus-tanah')->close();
        Flux::toast(variant: 'success', text: 'Data tanah wakaf berhasil dihapus.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Data Wakaf</flux:heading>
            <flux:text class="mt-1">
                @if (auth()->user()->isKua())
                    Data tanah wakaf di Kecamatan {{ auth()->user()->kecamatan?->nama }} (hanya lihat).
                @else
                    Kelola data tanah wakaf di Kabupaten Batang Hari.
                @endif
            </flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('impor', \App\Models\TanahWakaf::class)
                <flux:button icon="arrow-up-tray" :href="route('admin.tanah.impor')" wire:navigate>Impor Excel</flux:button>
            @endcan
            @can('create', \App\Models\TanahWakaf::class)
                <flux:button variant="primary" icon="plus" :href="route('admin.tanah.create')" wire:navigate>Tambah Data</flux:button>
            @endcan
        </div>
    </div>

    <div class="kartu space-y-4 p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="sm:col-span-2">
                <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nama, kode, atau wakif..." maxlength="100" aria-label="Cari" />
            </div>
            <flux:select wire:model.live="kecamatan" aria-label="Kecamatan">
                <flux:select.option value="">Semua Kecamatan</flux:select.option>
                @foreach ($this->daftarKecamatan as $id => $nama)
                    <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="desa" aria-label="Desa" :disabled="! $this->daftarDesa">
                <flux:select.option value="">Semua Desa</flux:select.option>
                @foreach ($this->daftarDesa as $id => $nama)
                    <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="jenis" aria-label="Jenis penggunaan">
                <flux:select.option value="">Semua Jenis</flux:select.option>
                @foreach (JenisPenggunaan::opsi() as $nilai => $label)
                    <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status" aria-label="Status sertifikat">
                <flux:select.option value="">Semua Status</flux:select.option>
                @foreach (StatusSertifikat::cases() as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="w-full sm:w-56">
                <flux:select wire:model.live="terbit" aria-label="Publikasi">
                    <flux:select.option value="">Semua Publikasi</flux:select.option>
                    <flux:select.option value="1">Dipublikasikan</flux:select.option>
                    <flux:select.option value="0">Belum Dipublikasikan</flux:select.option>
                </flux:select>
            </div>
            <div class="flex items-center gap-3 text-sm text-zinc-600">
                <span>{{ number_format($this->tanahs->total(), 0, ',', '.') }} data</span>
                <flux:button size="sm" variant="ghost" wire:click="resetFilter">Reset filter</flux:button>
            </div>
        </div>
    </div>

    <div class="kartu overflow-hidden p-5">
        @if ($this->tanahs->isEmpty())
            <x-empty-state icon="map-pin" title="Belum ada data tanah wakaf" description="Tidak ada data yang sesuai dengan filter." />
        @else
            <div class="overflow-x-auto">
                <flux:table :paginate="$this->tanahs">
                    <flux:table.columns>
                        <flux:table.column>Kode</flux:table.column>
                        <flux:table.column>Nama Tanah Wakaf</flux:table.column>
                        <flux:table.column>Lokasi</flux:table.column>
                        <flux:table.column>Luas</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Publikasi</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->tanahs as $tanah)
                            <flux:table.row :key="$tanah->id">
                                <flux:table.cell class="font-mono text-xs">{{ $tanah->kode }}</flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-medium text-zinc-800">{{ $tanah->nama }}</p>
                                    <p class="text-xs text-zinc-500">{{ $tanah->jenis_penggunaan->label() }} · Wakif: {{ $tanah->wakif_nama }}</p>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <p>{{ $tanah->desa?->nama }}</p>
                                    <p class="text-xs text-zinc-500">{{ $tanah->desa?->kecamatan?->nama }}</p>
                                </flux:table.cell>
                                <flux:table.cell>{{ format_luas($tanah->luas_m2) }}</flux:table.cell>
                                <flux:table.cell><x-badge-status :status="$tanah->status_sertifikat" /></flux:table.cell>
                                <flux:table.cell>
                                    @if ($tanah->is_published)
                                        <flux:badge size="sm" color="green">Terbit</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">Belum</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="lihat({{ $tanah->id }})" aria-label="Detail {{ $tanah->nama }}" />
                                        @can('update', $tanah)
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.tanah.edit', $tanah)" wire:navigate aria-label="Ubah {{ $tanah->nama }}" />
                                        @endcan
                                        @can('delete', $tanah)
                                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600!" wire:click="konfirmasiHapus({{ $tanah->id }})" aria-label="Hapus {{ $tanah->nama }}" />
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

    <flux:modal name="detail-tanah" class="w-full max-w-2xl">
        @if ($tanah = $this->detail)
            @php
                $tgl = fn ($d) => $d?->locale('id')->translatedFormat('j F Y');
                $nik = $tanah->wakif_nik ? str_repeat('•', max(strlen($tanah->wakif_nik) - 4, 0)).substr($tanah->wakif_nik, -4) : null;
                $rincian = [
                    'Kode' => $tanah->kode,
                    'Jenis Penggunaan' => $tanah->jenis_penggunaan->label(),
                    'Nazir' => $tanah->namaNazirTampil(),
                    'Wakif' => $tanah->wakif_nama,
                    'NIK Wakif' => $nik,
                    'Desa/Kelurahan' => $tanah->desa?->namaLengkap(),
                    'Kecamatan' => $tanah->desa?->kecamatan?->nama,
                    'Alamat' => $tanah->alamat,
                    'Luas' => format_luas($tanah->luas_m2),
                    'Status Sertifikat' => $tanah->status_sertifikat->label(),
                    'Nomor AIW' => $tanah->nomor_aiw,
                    'Tanggal AIW' => $tgl($tanah->tanggal_aiw),
                    'Nomor Sertifikat' => $tanah->nomor_sertifikat,
                    'Tanggal Sertifikat' => $tgl($tanah->tanggal_sertifikat),
                    'Koordinat' => number_format((float) $tanah->latitude, 6, '.', '').', '.number_format((float) $tanah->longitude, 6, '.', ''),
                    'Publikasi' => $tanah->is_published ? 'Dipublikasikan' : 'Belum dipublikasikan',
                    'Sumber Data' => ucfirst($tanah->sumber),
                ];
            @endphp
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $tanah->nama }}</flux:heading>
                    <flux:text class="mt-1">{{ $tanah->kode }}</flux:text>
                </div>
                @if ($tanah->fotoUrl())
                    <img src="{{ $tanah->fotoUrl() }}" alt="Foto {{ $tanah->nama }}" class="h-48 w-full rounded-xl object-cover">
                @endif
                <dl class="divide-y divide-zinc-100 border-y border-zinc-100">
                    @foreach ($rincian as $label => $nilai)
                        <div class="grid grid-cols-[10rem_1fr] gap-3 py-2 text-sm">
                            <dt class="text-zinc-500">{{ $label }}</dt>
                            <dd class="font-medium text-zinc-800">{{ filled($nilai) ? $nilai : '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <x-peta mode="data" wire:key="peta-detail-{{ $tanah->id }}" :data="[[...$tanah->dataPublik(), 'url' => null]]" :tombol-detail="false" :scroll-zoom="false" :legenda="false" tinggi="h-56" />
                <div class="flex justify-end gap-2">
                    @can('update', $tanah)
                        <flux:button variant="primary" icon="pencil-square" :href="route('admin.tanah.edit', $tanah)" wire:navigate>Ubah</flux:button>
                    @endcan
                    <flux:modal.close><flux:button variant="ghost">Tutup</flux:button></flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="hapus-tanah" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus data tanah wakaf?</flux:heading>
            <flux:text>Data yang dihapus tidak bisa dikembalikan. Tanah yang memiliki pengajuan tidak dapat dihapus.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="hapus">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
