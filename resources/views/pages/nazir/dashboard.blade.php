<?php

use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusValidasiDokumen;
use App\Models\Pengajuan;
use App\Models\Persyaratan;
use App\Models\TanahWakaf;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * @return array{tanah: int, diproses: int, selesai: int, belum_lengkap: int, total_pengajuan: int}
     */
    #[Computed]
    public function statistik(): array
    {
        $user = auth()->user();
        $perStatus = Pengajuan::query()->forUser($user)
            ->selectRaw('status, count(*) as jumlah')->groupBy('status')
            ->pluck('jumlah', 'status');

        $hitung = fn (array $statuses) => (int) collect($statuses)->sum(fn (StatusPengajuan $s) => $perStatus[$s->value] ?? 0);

        return [
            'tanah' => TanahWakaf::query()->forUser($user)->count(),
            'diproses' => $hitung([StatusPengajuan::Diajukan, StatusPengajuan::PerluPerbaikan, StatusPengajuan::Terverifikasi]),
            'selesai' => $hitung([StatusPengajuan::AiwDisahkan, StatusPengajuan::SertifikatTerbit]),
            'belum_lengkap' => $this->jumlahDokumenBelumLengkap(),
            'total_pengajuan' => (int) $perStatus->sum(),
        ];
    }

    /**
     * Pengajuan yang masih bisa diubah Nazir (draf/perlu perbaikan) dan dokumen wajibnya belum lengkap
     * atau masih ada dokumen yang ditandai tidak valid.
     */
    protected function jumlahDokumenBelumLengkap(): int
    {
        $wajib = Persyaratan::query()->where('wajib', true)->get(['id', 'jenis_pengajuan'])
            ->groupBy(fn (Persyaratan $p) => $p->jenis_pengajuan->value)
            ->map(fn ($grup) => $grup->pluck('id')->all());

        return Pengajuan::query()->forUser(auth()->user())
            ->whereIn('status', [StatusPengajuan::Draf->value, StatusPengajuan::PerluPerbaikan->value])
            ->with(['dokumens' => fn ($q) => $q->where('kategori', KategoriDokumen::Persyaratan->value)
                ->select('id', 'pengajuan_id', 'persyaratan_id', 'status_validasi')])
            ->get()
            ->filter(function (Pengajuan $p) use ($wajib) {
                $kurang = array_diff($wajib[$p->jenis->value] ?? [], $p->dokumens->pluck('persyaratan_id')->all());

                return $kurang !== [] || $p->dokumens->contains('status_validasi', StatusValidasiDokumen::TidakValid);
            })
            ->count();
    }

    /**
     * @return Collection<int, Pengajuan>
     */
    #[Computed]
    public function terbaru(): Collection
    {
        return Pengajuan::query()->forUser(auth()->user())
            ->with('tanahWakaf.desa.kecamatan')
            ->latest('updated_at')->latest('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Pengajuan>
     */
    #[Computed]
    public function perluPerbaikan(): Collection
    {
        return Pengajuan::query()->forUser(auth()->user())
            ->where('status', StatusPengajuan::PerluPerbaikan->value)
            ->with('tanahWakaf')
            ->latest('updated_at')
            ->get();
    }

    /**
     * @return Collection<int, TanahWakaf>
     */
    #[Computed]
    public function tanahs(): Collection
    {
        return TanahWakaf::query()->forUser(auth()->user())
            ->with(['desa.kecamatan', 'nazir', 'pengajuans:id,tanah_wakaf_id'])
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Selamat Datang, {{ auth()->user()->name }}</flux:heading>
            <flux:text class="mt-1">Nazir · Kelola data tanah wakaf dan pantau pengajuan Anda.</flux:text>
        </div>
        <flux:button :href="route('nazir.pengajuan.create')" wire:navigate variant="primary" icon="plus">Buat Pengajuan</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card icon="map-pin" label="Data Wakaf Saya" :value="$this->statistik['tanah']" satuan="Bidang" :href="route('nazir.tanah')" />
        <x-stat-card icon="arrow-path" label="Pengajuan Diproses" :value="$this->statistik['diproses']" satuan="Pengajuan" :href="route('nazir.pengajuan.index')" />
        <x-stat-card icon="check-badge" label="Pengajuan Selesai" :value="$this->statistik['selesai']" satuan="Pengajuan" :href="route('nazir.pengajuan.index')" />
        <x-stat-card icon="document-magnifying-glass" label="Dokumen Belum Lengkap" :value="$this->statistik['belum_lengkap']" satuan="Pengajuan" :href="route('nazir.dokumen')" />
    </div>

    @if ($this->statistik['total_pengajuan'] === 0)
        <flux:callout icon="light-bulb" color="emerald">
            <flux:callout.heading>Anda belum memiliki pengajuan</flux:callout.heading>
            <flux:callout.text>
                Mulai dengan mengajukan pembuatan Akta Ikrar Wakaf (AIW) untuk tanah wakaf yang Anda kelola,
                atau ajukan sertifikat wakaf untuk tanah yang AIW-nya sudah disahkan.
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button :href="route('nazir.pengajuan.create')" wire:navigate variant="primary" size="sm">Buat Pengajuan</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if ($this->perluPerbaikan->isNotEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>{{ $this->perluPerbaikan->count() }} pengajuan perlu perbaikan</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-1 space-y-1">
                    @foreach ($this->perluPerbaikan as $p)
                        <li wire:key="perbaikan-{{ $p->id }}">
                            <a href="{{ route('nazir.pengajuan.show', $p) }}" wire:navigate class="font-medium underline">
                                {{ $p->nomor ?? 'Draf' }} · {{ $p->tanahWakaf?->nama }}
                            </a>
                            @if ($p->catatan_pemeriksa)
                                <span class="text-amber-800">: {{ \Illuminate\Support\Str::limit($p->catatan_pemeriksa, 100) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="kartu overflow-hidden xl:col-span-3" aria-labelledby="judul-terbaru">
            <div class="flex items-center justify-between gap-2 px-5 pt-5">
                <h2 id="judul-terbaru" class="font-semibold text-primary-900">Data Terbaru</h2>
                <a href="{{ route('nazir.pengajuan.index') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:underline">Lihat semua</a>
            </div>

            @if ($this->terbaru->isEmpty())
                <x-empty-state icon="clipboard-document-list" title="Belum ada pengajuan" description="Pengajuan yang Anda buat akan tampil di sini." />
            @else
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="mt-3 w-full text-sm">
                        <thead>
                            <tr class="border-b border-primary-100 text-start text-xs uppercase tracking-wide text-zinc-500">
                                <th class="hidden py-2 pe-3 text-start font-medium sm:table-cell">No</th>
                                <th class="py-2 pe-3 text-start font-medium">Nama Tanah Wakaf</th>
                                <th class="hidden py-2 pe-3 text-start font-medium sm:table-cell">Lokasi</th>
                                <th class="py-2 pe-3 text-start font-medium">Status</th>
                                <th class="py-2 text-end font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary-50">
                            @foreach ($this->terbaru as $p)
                                <tr wire:key="terbaru-{{ $p->id }}" @class(['bg-amber-50/60' => $p->status === \App\Enums\StatusPengajuan::PerluPerbaikan])>
                                    <td class="hidden py-3 pe-3 text-zinc-500 sm:table-cell">{{ $loop->iteration }}</td>
                                    <td class="py-3 pe-3">
                                        <p class="font-medium text-primary-900">{{ $p->tanahWakaf?->nama }}</p>
                                        <p class="text-xs text-zinc-500">{{ $p->nomor ?? 'Draf' }} · {{ $p->jenis->labelSingkat() }}</p>
                                        <p class="text-xs text-zinc-500 sm:hidden">{{ $p->tanahWakaf?->desa?->kecamatan?->nama }}</p>
                                    </td>
                                    <td class="hidden py-3 pe-3 text-zinc-600 sm:table-cell">{{ $p->tanahWakaf?->desa?->nama }}, {{ $p->tanahWakaf?->desa?->kecamatan?->nama }}</td>
                                    <td class="py-3 pe-3"><x-badge-status :status="$p->status" /></td>
                                    <td class="py-3 text-end">
                                        <flux:button size="sm" variant="ghost" :href="route('nazir.pengajuan.show', $p)" wire:navigate>Detail</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="kartu space-y-3 p-5 xl:col-span-2" aria-labelledby="judul-peta">
            <div class="flex items-center justify-between gap-2">
                <h2 id="judul-peta" class="font-semibold text-primary-900">Peta Wakaf Saya</h2>
                <flux:button size="sm" variant="primary" icon="map" :href="route('nazir.peta')" wire:navigate>Lihat Peta</flux:button>
            </div>
            <x-nazir.peta-tanah :tanahs="$this->tanahs" tinggi="h-72" :scroll-zoom="false" :legenda="false" />
        </section>
    </div>
</div>
