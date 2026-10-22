<?php

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Services\StatistikWakaf;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    protected function pengguna(): User
    {
        return auth()->user();
    }

    /**
     * @return array{total: int, sudah: int, belum: int, proses: int, kecamatan: int, desa: int, luas_m2: float}
     */
    #[Computed]
    public function ringkasan(): array
    {
        return app(StatistikWakaf::class)->ringkasan($this->pengguna());
    }

    #[Computed]
    public function pengajuanBaru(): int
    {
        return app(StatistikWakaf::class)->pengajuanPerStatus($this->pengguna())[StatusPengajuan::Diajukan->value] ?? 0;
    }

    /**
     * @return array<int, array{kecamatan: string, total: int, sudah: int, belum: int, proses: int}>
     */
    #[Computed]
    public function sebaran(): array
    {
        return app(StatistikWakaf::class)->perKecamatan($this->pengguna())->all();
    }

    /**
     * Data peta mini (semua tanah dalam cakupan, termasuk yang belum dipublikasikan).
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function dataPeta(): array
    {
        $user = $this->pengguna();
        $bisaUbah = $user->can('create', TanahWakaf::class);

        return TanahWakaf::query()->forUser($user)
            ->with(['desa.kecamatan', 'nazir'])
            ->get()
            ->map(fn (TanahWakaf $t) => [...$t->dataPublik(), 'url' => $bisaUbah ? route('admin.tanah.edit', $t) : null])
            ->all();
    }

    /**
     * Pengajuan yang menunggu tindakan pengguna ini.
     *
     * @return Collection<int, Pengajuan>
     */
    #[Computed]
    public function perluDiperiksa(): Collection
    {
        $user = $this->pengguna();

        return Pengajuan::query()->forUser($user)
            ->where(function ($q) use ($user) {
                if ($user->isBpn()) {
                    $q->where(fn ($q) => $q->where('status', StatusPengajuan::Diajukan->value)
                        ->whereIn('jenis', [JenisPengajuan::SertifikatBersertifikat->value, JenisPengajuan::SertifikatBelum->value]))
                        ->orWhere('status', StatusPengajuan::Terverifikasi->value);
                } elseif ($user->isKua()) {
                    $q->where('status', StatusPengajuan::Diajukan->value)->where('jenis', JenisPengajuan::Aiw->value);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->with(['tanahWakaf.desa.kecamatan', 'nazir'])
            ->oldest('diajukan_at')
            ->limit(20)
            ->get()
            ->filter(fn (Pengajuan $p) => $user->can('periksa', $p))
            ->take(6)
            ->values();
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman :judul="'Selamat Datang, '.auth()->user()->name">
        <x-slot:keterangan>
            {{ now()->locale('id')->translatedFormat('l, j F Y') }}
            @if (auth()->user()->isKua() && auth()->user()->kecamatan)
                · Wilayah {{ auth()->user()->kecamatan->nama }}
            @endif
        </x-slot:keterangan>
    </x-admin.judul-halaman>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card icon="map-pin" label="Total Tanah Wakaf" :value="$this->ringkasan['total']" satuan="Bidang" :href="route('admin.tanah.index')" />
        <x-stat-card icon="check-badge" label="Bersertifikat" :value="$this->ringkasan['sudah']" satuan="Bidang" :href="route('admin.tanah.index', ['status' => 'sudah'])" />
        <x-stat-card icon="exclamation-triangle" label="Belum Bersertifikat" :value="$this->ringkasan['belum'] + $this->ringkasan['proses']"
            satuan="Bidang ({{ $this->ringkasan['proses'] }} dalam proses)" :href="route('admin.tanah.index', ['status' => 'belum'])" />
        <x-stat-card icon="inbox-arrow-down" label="Pengajuan Baru" :value="$this->pengajuanBaru" satuan="Menunggu pemeriksaan"
            :href="route('admin.pengajuan.index', ['status' => 'diajukan'])" />
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="kartu p-5 xl:col-span-3" aria-labelledby="judul-grafik">
            <h2 id="judul-grafik" class="font-semibold text-primary-900">Sebaran Tanah Wakaf per Kecamatan</h2>
            <div wire:ignore class="mt-4">
                <div x-data="grafikSebaran(@js($this->sebaran))" class="h-72">
                    <canvas x-ref="canvas" aria-label="Grafik batang sebaran tanah wakaf per kecamatan" role="img"></canvas>
                </div>
            </div>
        </section>

        <section class="kartu p-5 xl:col-span-2" aria-labelledby="judul-peta">
            <div class="flex items-center justify-between gap-3">
                <h2 id="judul-peta" class="font-semibold text-primary-900">Peta Sebaran Tanah Wakaf</h2>
                <a href="{{ route('admin.peta') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:underline">Lihat Peta Lengkap</a>
            </div>
            <x-peta mode="data" :data="$this->dataPeta" tinggi="h-72" :scroll-zoom="false" :legenda="false" class="mt-4" />
        </section>
    </div>

    <section class="kartu p-5" aria-labelledby="judul-periksa">
        <div class="flex items-center justify-between gap-3">
            <h2 id="judul-periksa" class="font-semibold text-primary-900">Perlu Diperiksa</h2>
            <a href="{{ route('admin.pengajuan.index', ['tab' => 'periksa']) }}" wire:navigate class="text-sm font-medium text-primary-700 hover:underline">Lihat semua</a>
        </div>

        @if ($this->perluDiperiksa->isEmpty())
            <x-empty-state icon="check-circle" title="Tidak ada pengajuan yang menunggu" description="Semua pengajuan dalam wilayah Anda sudah ditangani." />
        @else
            <ul class="mt-3 divide-y divide-zinc-100">
                @foreach ($this->perluDiperiksa as $p)
                    <li wire:key="periksa-{{ $p->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-800">{{ $p->tanahWakaf->nama }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ $p->nomor }} · {{ $p->jenis->labelSingkat() }} · {{ $p->nazir->name }}
                                · {{ $p->tanahWakaf->desa?->kecamatan?->nama }}
                                · {{ $p->diajukan_at?->locale('id')->translatedFormat('j M Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge-status :status="$p->status" />
                            <flux:button size="sm" variant="primary" :href="route('admin.pengajuan.show', $p)" wire:navigate>Periksa</flux:button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
