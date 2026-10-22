<?php

use App\Models\Kecamatan;
use App\Support\FilterLaporan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Laporan')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $kecamatan = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $jenis = '';

    #[Url(except: '')]
    public string $dari = '';

    #[Url(except: '')]
    public string $sampai = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('laporan.lihat'), 403);
    }

    public function mount(): void
    {
        $this->kunciKecamatan();
    }

    public function updated(string $properti): void
    {
        $this->kunciKecamatan();
        $this->resetPage();
        unset($this->filter);
    }

    /** KUA selalu dikunci ke kecamatannya sendiri. */
    private function kunciKecamatan(): void
    {
        $user = auth()->user();
        if ($user->isKua()) {
            $this->kecamatan = (string) $user->kecamatan_id;
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['kecamatan', 'status', 'jenis', 'dari', 'sampai']);
        $this->kunciKecamatan();
        $this->resetPage();
        unset($this->filter);
    }

    #[Computed]
    public function filter(): FilterLaporan
    {
        return FilterLaporan::dari(auth()->user(), [
            'kecamatan' => $this->kecamatan,
            'status' => $this->status,
            'jenis' => $this->jenis,
            'dari' => $this->dari,
            'sampai' => $this->sampai,
        ]);
    }

    #[Computed]
    public function kecamatans()
    {
        return Kecamatan::query()->orderBy('nama')->get(['id', 'nama']);
    }

    public function with(): array
    {
        $filter = $this->filter;

        return [
            'ringkasan' => $filter->ringkasan(),
            'rekap' => $filter->perKecamatan(),
            'rekapPengajuan' => $filter->rekapPengajuan(),
            'tanahs' => $filter->tanahTerurut()->paginate(15),
            'kueri' => $filter->keQuery(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman judul="Laporan" :deskripsi="implode(' · ', $this->filter->uraian())">
        @can('laporan.ekspor')
            <flux:button icon="table-cells" :href="route('admin.laporan.excel', $kueri)">Ekspor Excel</flux:button>
            <flux:button variant="primary" icon="document-arrow-down" :href="route('admin.laporan.pdf', $kueri)" target="_blank">Ekspor PDF</flux:button>
        @endcan
    </x-admin.judul-halaman>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        @if (auth()->user()->isKua())
            <flux:input label="Kecamatan" :value="auth()->user()->kecamatan?->nama" disabled />
        @else
            <flux:select wire:model.live="kecamatan" label="Kecamatan">
                <flux:select.option value="">Semua kecamatan</flux:select.option>
                @foreach ($this->kecamatans as $k)
                    <flux:select.option value="{{ $k->id }}">{{ $k->nama }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
        <flux:select wire:model.live="status" label="Status Sertifikat">
            <flux:select.option value="">Semua status</flux:select.option>
            @foreach (\App\Enums\StatusSertifikat::cases() as $s)
                <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="jenis" label="Jenis Penggunaan">
            <flux:select.option value="">Semua jenis</flux:select.option>
            @foreach (\App\Enums\JenisPenggunaan::opsi() as $nilai => $label)
                <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="dari" label="Pengajuan dari" />
        <flux:input type="date" wire:model.live="sampai" label="Sampai" />
        <div class="flex items-end">
            <flux:button variant="ghost" icon="arrow-path" wire:click="resetFilter" class="w-full">Reset</flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card icon="map-pin" label="Total Tanah Wakaf" :value="$ringkasan['total']" satuan="Bidang" />
        <x-stat-card icon="shield-check" label="Sudah Bersertifikat" :value="$ringkasan['sudah']" satuan="Bidang" />
        <x-stat-card icon="clock" label="Dalam Proses" :value="$ringkasan['proses']" satuan="Bidang" />
        <x-stat-card icon="exclamation-triangle" label="Belum Bersertifikat" :value="$ringkasan['belum']" satuan="Bidang" />
    </div>
    <flux:text class="text-xs">
        Total luas {{ format_luas($ringkasan['luas_m2']) }}, tersebar di {{ $ringkasan['kecamatan'] }} kecamatan dan {{ $ringkasan['desa'] }} desa/kelurahan.
    </flux:text>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="kartu p-5 lg:col-span-2">
            <flux:heading size="lg" class="mb-3">Sebaran per Kecamatan</flux:heading>
            <div wire:ignore wire:key="grafik-{{ md5(json_encode($rekap)) }}" x-data="grafikSebaran(@js($rekap))" class="h-72">
                <canvas x-ref="canvas"></canvas>
            </div>
        </section>

        <section class="kartu p-5">
            <flux:heading size="lg">Rekap Pengajuan</flux:heading>
            <flux:text class="mb-3 text-xs">Mengikuti filter kecamatan, jenis penggunaan, dan periode pengajuan.</flux:text>
            <ul class="divide-y divide-primary-50">
                @foreach (\App\Enums\StatusPengajuan::cases() as $s)
                    <li class="flex items-center justify-between py-2">
                        <x-badge-status :status="$s" />
                        <span class="font-semibold text-primary-900">{{ number_format($rekapPengajuan[$s->value] ?? 0, 0, ',', '.') }}</span>
                    </li>
                @endforeach
                <li class="flex items-center justify-between py-2 font-semibold">
                    <span>Total</span>
                    <span>{{ number_format(array_sum($rekapPengajuan), 0, ',', '.') }}</span>
                </li>
            </ul>
        </section>
    </div>

    <section class="kartu overflow-hidden">
        <div class="border-b border-primary-100 px-5 py-3">
            <flux:heading size="lg">Rekap per Kecamatan</flux:heading>
        </div>
        <flux:table class="[&_td]:px-3 [&_th]:px-3">
            <flux:table.columns>
                <flux:table.column>Kecamatan</flux:table.column>
                <flux:table.column align="end">Sudah</flux:table.column>
                <flux:table.column align="end">Proses</flux:table.column>
                <flux:table.column align="end">Belum</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column align="end">Luas</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($rekap as $r)
                    <flux:table.row :key="'rekap-'.$loop->index">
                        <flux:table.cell>{{ $r['kecamatan'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $r['sudah'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $r['proses'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $r['belum'] }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-semibold">{{ $r['total'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ format_luas($r['luas_m2']) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </section>

    <section class="kartu overflow-hidden">
        <div class="border-b border-primary-100 px-5 py-3">
            <flux:heading size="lg">Data Tanah Wakaf</flux:heading>
        </div>
        @if ($tanahs->isEmpty())
            <x-empty-state icon="map-pin" title="Tidak ada data" description="Tidak ada tanah wakaf sesuai filter." />
        @else
            <flux:table :paginate="$tanahs" class="[&_td]:px-3 [&_th]:px-3">
                <flux:table.columns>
                    <flux:table.column>Kode</flux:table.column>
                    <flux:table.column>Nama</flux:table.column>
                    <flux:table.column>Lokasi</flux:table.column>
                    <flux:table.column align="end">Luas</flux:table.column>
                    <flux:table.column>Penggunaan</flux:table.column>
                    <flux:table.column>Nazir</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($tanahs as $t)
                        <flux:table.row :key="'tanah-'.$t->id">
                            <flux:table.cell class="font-mono text-xs">{{ $t->kode }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal font-medium text-primary-900">{{ $t->nama }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal text-sm">{{ $t->desa?->namaLengkap() }}, {{ $t->desa?->kecamatan?->nama }}</flux:table.cell>
                            <flux:table.cell align="end">{{ format_luas($t->luas_m2) }}</flux:table.cell>
                            <flux:table.cell>{{ $t->jenis_penggunaan->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $t->namaNazirTampil() ?? '—' }}</flux:table.cell>
                            <flux:table.cell><x-badge-status :status="$t->status_sertifikat" /></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </section>
</div>
