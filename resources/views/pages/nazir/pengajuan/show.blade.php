<?php

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\PengajuanDokumen;
use App\Services\PengajuanWorkflow;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Detail pengajuan Nazir: ringkasan tanah, checklist & unggah dokumen, kirim/kirim ulang,
 * dokumen hasil, dan riwayat status. Semua perubahan status lewat PengajuanWorkflow.
 */
new class extends Component {
    use WithFileUploads;

    public Pengajuan $pengajuan;

    /** @var array<int|string, mixed> berkas unggahan per persyaratan_id */
    public array $berkas = [];

    public bool $mengubahTanah = false;

    // Form ubah data tanah (khusus AIW)
    public string $nama = '';

    public string $jenis_penggunaan = '';

    public string $wakif_nama = '';

    public string $wakif_nik = '';

    public string $kecamatan_id = '';

    public string $desa_id = '';

    public string $alamat = '';

    public $luas_m2 = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?array $batas_geojson = null;

    public function mount(Pengajuan $pengajuan): void
    {
        $this->authorize('view', $pengajuan);
        $this->pengajuan = $pengajuan;
    }

    public function render()
    {
        return $this->view()->title($this->pengajuan->nomor ?? 'Draf Pengajuan');
    }

    /**
     * Livewire memanggil hook ini setelah satu berkas selesai diunggah (wire:model="berkas.{id}").
     */
    public function updatedBerkas($value, $key): void
    {
        $this->authorize('update', $this->pengajuan);

        $persyaratan = $this->pengajuan->persyaratans()->firstWhere('id', (int) $key);
        if (! $persyaratan) {
            unset($this->berkas[$key]);

            return;
        }

        $this->validate(
            ["berkas.{$key}" => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']],
            [
                "berkas.{$key}.mimes" => 'Format file harus PDF, JPG, atau PNG.',
                "berkas.{$key}.max" => 'Ukuran file maksimal 5 MB.',
            ],
            ["berkas.{$key}" => 'berkas'],
        );

        if (! $this->bisaGanti($persyaratan->id)) {
            unset($this->berkas[$key]);
            throw ValidationException::withMessages([
                "berkas.{$key}" => 'Hanya dokumen yang ditandai tidak valid yang bisa diganti saat perbaikan.',
            ]);
        }

        app(PengajuanWorkflow::class)->unggahDokumen($this->pengajuan, $persyaratan, $value, auth()->user());

        unset($this->berkas[$key]);
        $this->pengajuan->refresh();
        unset($this->dokumenPersyaratan);

        Flux::toast(variant: 'success', text: "Dokumen \"{$persyaratan->nama}\" berhasil diunggah.");
    }

    public function kirim(PengajuanWorkflow $workflow): void
    {
        $this->authorize('kirim', $this->pengajuan);

        $ulang = $this->pengajuan->status === StatusPengajuan::PerluPerbaikan;
        $workflow->kirim($this->pengajuan, auth()->user());

        $this->pengajuan->refresh();
        $this->mengubahTanah = false;
        unset($this->dokumenPersyaratan);
        Flux::modal('konfirmasi-kirim')->close();

        Flux::toast(variant: 'success', text: $ulang
            ? 'Pengajuan berhasil dikirim ulang ke pemeriksa.'
            : "Pengajuan {$this->pengajuan->nomor} berhasil dikirim ke pemeriksa.");
    }

    public function mulaiUbahTanah(): void
    {
        $this->authorize('update', $this->pengajuan);
        abort_unless($this->pengajuan->jenis === JenisPengajuan::Aiw, 403);

        $tanah = $this->pengajuan->tanahWakaf()->with('desa')->firstOrFail();

        $this->nama = $tanah->nama;
        $this->jenis_penggunaan = $tanah->jenis_penggunaan->value;
        $this->wakif_nama = $tanah->wakif_nama;
        $this->wakif_nik = (string) $tanah->wakif_nik;
        $this->kecamatan_id = (string) $tanah->desa?->kecamatan_id;
        $this->desa_id = (string) $tanah->desa_id;
        $this->alamat = (string) $tanah->alamat;
        $this->luas_m2 = (string) (float) $tanah->luas_m2;
        $this->latitude = (float) $tanah->latitude;
        $this->longitude = (float) $tanah->longitude;
        $this->batas_geojson = $tanah->batas_geojson;

        $this->resetErrorBag();
        $this->mengubahTanah = true;
    }

    public function batalUbahTanah(): void
    {
        $this->resetErrorBag();
        $this->mengubahTanah = false;
    }

    public function updatedKecamatanId(): void
    {
        $this->desa_id = '';
    }

    public function simpanTanah(): void
    {
        $this->authorize('update', $this->pengajuan);
        abort_unless($this->pengajuan->jenis === JenisPengajuan::Aiw, 403);

        $this->wakif_nik = preg_replace('/\s+/', '', $this->wakif_nik);

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_penggunaan' => ['required', Rule::enum(JenisPenggunaan::class)],
            'wakif_nama' => ['required', 'string', 'max:255'],
            'wakif_nik' => ['nullable', 'digits:16'],
            'kecamatan_id' => ['required', 'integer', Rule::exists('kecamatans', 'id')],
            'desa_id' => ['required', 'integer', Rule::exists('desas', 'id')->where('kecamatan_id', (int) $this->kecamatan_id)],
            'alamat' => ['nullable', 'string', 'max:500'],
            'luas_m2' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'batas_geojson' => ['nullable', 'array'],
            'batas_geojson.type' => ['required_with:batas_geojson', 'in:Polygon,MultiPolygon'],
            'batas_geojson.coordinates' => ['required_with:batas_geojson', 'array'],
        ], [
            'latitude.required' => 'Tentukan titik lokasi tanah di peta atau isi koordinat secara manual.',
        ], [
            'nama' => 'nama tanah wakaf',
            'jenis_penggunaan' => 'jenis penggunaan',
            'wakif_nama' => 'nama wakif',
            'wakif_nik' => 'NIK wakif',
            'kecamatan_id' => 'kecamatan',
            'desa_id' => 'desa/kelurahan',
            'luas_m2' => 'luas',
        ]);

        app(PengajuanWorkflow::class)->ubahDataTanah($this->pengajuan, auth()->user(), [
            'nama' => trim($this->nama),
            'jenis_penggunaan' => JenisPenggunaan::from($this->jenis_penggunaan),
            'wakif_nama' => trim($this->wakif_nama),
            'wakif_nik' => filled($this->wakif_nik) ? $this->wakif_nik : null,
            'desa_id' => (int) $this->desa_id,
            'alamat' => filled(trim($this->alamat)) ? trim($this->alamat) : null,
            'luas_m2' => (float) $this->luas_m2,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'batas_geojson' => $this->batas_geojson ?: null,
        ]);

        $this->pengajuan->refresh();
        $this->mengubahTanah = false;

        Flux::toast(variant: 'success', text: 'Data tanah berhasil diperbarui.');
    }

    /**
     * Dokumen persyaratan yang boleh diunggah/diganti Nazir saat ini.
     * Draf: semua. Perlu perbaikan: hanya yang belum ada atau ditandai tidak valid.
     */
    protected function bisaGanti(int $persyaratanId): bool
    {
        if (! auth()->user()->can('update', $this->pengajuan)) {
            return false;
        }

        if ($this->pengajuan->status !== StatusPengajuan::PerluPerbaikan) {
            return true;
        }

        $dokumen = $this->dokumenPersyaratan->get($persyaratanId);

        return ! $dokumen || $dokumen->status_validasi === StatusValidasiDokumen::TidakValid;
    }

    /**
     * @return Collection<int, PengajuanDokumen> dokumen persyaratan, dikunci per persyaratan_id
     */
    #[Computed]
    public function dokumenPersyaratan(): Collection
    {
        return $this->pengajuan->dokumens()
            ->where('kategori', KategoriDokumen::Persyaratan->value)
            ->get()
            ->keyBy('persyaratan_id');
    }

    /**
     * @return Collection<int, PengajuanDokumen>
     */
    #[Computed]
    public function dokumenHasil(): Collection
    {
        return $this->pengajuan->dokumens()
            ->whereIn('kategori', [KategoriDokumen::HasilAiw->value, KategoriDokumen::HasilSertifikat->value])
            ->latest('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function kecamatans(): array
    {
        return Kecamatan::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function desas(): array
    {
        if (blank($this->kecamatan_id)) {
            return [];
        }

        return Desa::query()->where('kecamatan_id', (int) $this->kecamatan_id)->orderBy('nama')->get()
            ->mapWithKeys(fn (Desa $d) => [$d->id => $d->namaLengkap()])->all();
    }
}; ?>

@php
    $pengajuan->loadMissing(['tanahWakaf.desa.kecamatan', 'tanahWakaf.nazir', 'pemeriksa']);
    $tanah = $pengajuan->tanahWakaf;
    $status = $pengajuan->status;
    $bisaUbah = auth()->user()->can('update', $pengajuan);
    $persyaratans = $pengajuan->persyaratans();
    $dokumens = $this->dokumenPersyaratan;
    $kurang = $persyaratans->filter(fn ($p) => $p->wajib && ! $dokumens->has($p->id));
    $adaTidakValid = $dokumens->contains(fn ($d) => $d->status_validasi === \App\Enums\StatusValidasiDokumen::TidakValid);
    $riwayats = $pengajuan->riwayats()->with('user')->get();
    $tgl = fn ($t) => $t?->locale('id')->translatedFormat('j F Y, H:i');
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-1">
            <a href="{{ route('nazir.pengajuan.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-primary-700 hover:underline">
                <flux:icon.arrow-left class="size-4" /> Daftar Pengajuan
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">{{ $pengajuan->nomor ?? 'Draf Pengajuan' }}</flux:heading>
                <x-badge-status :status="$status" />
            </div>
            <flux:text>{{ $pengajuan->jenis->label() }}</flux:text>
            <p class="text-xs text-zinc-500">
                Dibuat {{ $tgl($pengajuan->created_at) }}
                @if ($pengajuan->diajukan_at) · Diajukan {{ $tgl($pengajuan->diajukan_at) }} @endif
                @if ($pengajuan->selesai_at) · Selesai {{ $tgl($pengajuan->selesai_at) }} @endif
            </p>
        </div>

        @if ($bisaUbah)
            <flux:modal.trigger name="konfirmasi-kirim">
                <flux:button variant="primary" icon="paper-airplane">
                    {{ $status === \App\Enums\StatusPengajuan::PerluPerbaikan ? 'Kirim Ulang' : 'Kirim Pengajuan' }}
                </flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    @session('sukses')
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ $value }}</flux:callout.text>
        </flux:callout>
    @endsession

    @if ($errors->hasAny(['dokumen', 'status', 'tanah_wakaf_id']))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Pengajuan belum bisa diproses</flux:callout.heading>
            @foreach (['dokumen', 'status', 'tanah_wakaf_id'] as $kunci)
                @error($kunci)
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                @enderror
            @endforeach
        </flux:callout>
    @endif

    @if (in_array($status, [\App\Enums\StatusPengajuan::PerluPerbaikan, \App\Enums\StatusPengajuan::Ditolak], true) && filled($pengajuan->catatan_pemeriksa))
        <flux:callout :variant="$status === \App\Enums\StatusPengajuan::Ditolak ? 'danger' : 'warning'" icon="chat-bubble-left-ellipsis">
            <flux:callout.heading>
                {{ $status === \App\Enums\StatusPengajuan::Ditolak ? 'Alasan penolakan' : 'Catatan perbaikan dari pemeriksa' }}
                @if ($pengajuan->pemeriksa) ({{ $pengajuan->pemeriksa->name }}) @endif
            </flux:callout.heading>
            <flux:callout.text class="whitespace-pre-line">{{ $pengajuan->catatan_pemeriksa }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($status === \App\Enums\StatusPengajuan::PerluPerbaikan)
        <flux:callout variant="warning" icon="arrow-path">
            <flux:callout.text>
                Ganti dokumen yang ditandai <strong>Tidak Valid</strong>, lalu tekan <strong>Kirim Ulang</strong>.
                Dokumen yang sudah valid tidak perlu diunggah lagi.
            </flux:callout.text>
        </flux:callout>
    @elseif ($status === \App\Enums\StatusPengajuan::Draf)
        <flux:callout icon="information-circle" color="sky">
            <flux:callout.text>
                Pengajuan masih berupa draf. Unggah semua dokumen wajib, lalu tekan <strong>Kirim Pengajuan</strong>.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Checklist dokumen --}}
            <section class="kartu p-5 sm:p-6" aria-labelledby="judul-dokumen">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="judul-dokumen" class="flex items-center gap-2 font-semibold text-primary-900">
                        <flux:icon.clipboard-document-check class="size-5 text-primary-700" /> Dokumen Persyaratan
                    </h2>
                    <span class="text-sm text-zinc-600">
                        {{ $dokumens->count() }} dari {{ $persyaratans->count() }} terunggah
                        @if ($kurang->isNotEmpty()) · <span class="text-amber-700">{{ $kurang->count() }} wajib belum ada</span> @endif
                    </span>
                </div>

                <ul class="divide-y divide-primary-100">
                    @foreach ($persyaratans as $syarat)
                        @php
                            $dok = $dokumens->get($syarat->id);
                            $bolehUnggah = $bisaUbah && ($status !== \App\Enums\StatusPengajuan::PerluPerbaikan || ! $dok
                                || $dok->status_validasi === \App\Enums\StatusValidasiDokumen::TidakValid);
                        @endphp
                        <li wire:key="syarat-{{ $syarat->id }}" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 gap-3">
                                <span class="mt-0.5 shrink-0">
                                    @if (! $dok)
                                        <flux:icon.minus-circle class="size-5 {{ $syarat->wajib ? 'text-amber-500' : 'text-zinc-300' }}" />
                                    @elseif ($dok->status_validasi === \App\Enums\StatusValidasiDokumen::TidakValid)
                                        <flux:icon.x-circle variant="solid" class="size-5 text-red-600" />
                                    @else
                                        <flux:icon.check-circle variant="solid" class="size-5 text-primary-600" />
                                    @endif
                                </span>
                                <div class="min-w-0 space-y-1">
                                    <p class="text-sm font-medium text-primary-900">
                                        {{ $loop->iteration }}. {{ $syarat->nama }}
                                        <span class="ms-1 text-xs font-normal {{ $syarat->wajib ? 'text-red-600' : 'text-zinc-500' }}">{{ $syarat->wajib ? 'Wajib' : 'Opsional' }}</span>
                                    </p>
                                    @if ($syarat->keterangan)
                                        <p class="text-xs text-zinc-500">{{ $syarat->keterangan }}</p>
                                    @endif
                                    @if ($dok)
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <a href="{{ $dok->url() }}" target="_blank" rel="noopener" class="inline-flex max-w-full items-center gap-1 truncate font-medium text-primary-700 hover:underline">
                                                <flux:icon.paper-clip class="size-3.5 shrink-0" /> <span class="truncate">{{ $dok->nama_asli }}</span>
                                            </a>
                                            <span class="text-zinc-400">{{ number_format($dok->ukuran / 1024, 0, ',', '.') }} KB</span>
                                            <x-badge-status :status="$dok->status_validasi" />
                                        </div>
                                        @if (filled($dok->catatan))
                                            <p class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs text-red-700">Catatan: {{ $dok->catatan }}</p>
                                        @endif
                                    @endif
                                    @error('berkas.'.$syarat->id)
                                        <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if ($bolehUnggah)
                                <div class="shrink-0" x-data="{ mengunggah: false }"
                                    x-on:livewire-upload-start="mengunggah = true"
                                    x-on:livewire-upload-finish="mengunggah = false"
                                    x-on:livewire-upload-error="mengunggah = false"
                                    x-on:livewire-upload-cancel="mengunggah = false">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-primary-700 bg-white px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50 focus-within:outline-2 focus-within:outline-primary-700">
                                        <flux:icon.arrow-up-tray class="size-4" x-show="! mengunggah" />
                                        <svg x-show="mengunggah" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" /><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" /></svg>
                                        <span x-text="mengunggah ? 'Mengunggah…' : '{{ $dok ? 'Ganti' : 'Unggah' }}'">{{ $dok ? 'Ganti' : 'Unggah' }}</span>
                                        <input type="file" class="sr-only" wire:model="berkas.{{ $syarat->id }}" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" aria-label="{{ $dok ? 'Ganti' : 'Unggah' }} {{ $syarat->nama }}">
                                    </label>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($bisaUbah)
                    <p class="mt-3 text-xs text-zinc-500">Format PDF, JPG, atau PNG, maksimal 5 MB per file. Dokumen tersimpan otomatis setelah dipilih.</p>
                @endif
            </section>

            {{-- Dokumen hasil --}}
            @if ($this->dokumenHasil->isNotEmpty())
                <section class="kartu p-5 sm:p-6" aria-labelledby="judul-hasil">
                    <h2 id="judul-hasil" class="mb-3 flex items-center gap-2 font-semibold text-primary-900">
                        <flux:icon.document-check class="size-5 text-primary-700" /> Dokumen Hasil
                    </h2>
                    <ul class="space-y-2">
                        @foreach ($this->dokumenHasil as $hasil)
                            <li wire:key="hasil-{{ $hasil->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-primary-50 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-primary-900">{{ $hasil->judul() }}</p>
                                    <p class="truncate text-xs text-zinc-600">{{ $hasil->nama_asli }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="$hasil->url()" target="_blank">Lihat</flux:button>
                                    <flux:button size="sm" variant="primary" icon="arrow-down-tray" :href="$hasil->url().'?unduh=1'">Unduh</flux:button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Ubah data tanah (AIW) --}}
            @if ($mengubahTanah && $bisaUbah && $pengajuan->jenis === \App\Enums\JenisPengajuan::Aiw)
                <section class="kartu p-5 sm:p-6" aria-labelledby="judul-ubah-tanah">
                    <form wire:submit="simpanTanah" class="space-y-5">
                        <h2 id="judul-ubah-tanah" class="font-semibold text-primary-900">Ubah Data Tanah</h2>
                        <x-nazir.form-tanah :kecamatans="$this->kecamatans" :desas="$this->desas" />

                        <flux:field>
                            <flux:label>Titik lokasi dan batas bidang</flux:label>
                            <x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" />
                            <flux:error name="latitude" />
                            <flux:error name="batas_geojson" />
                        </flux:field>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <flux:input wire:model="latitude" label="Latitude" type="number" step="any" />
                            <flux:input wire:model="longitude" label="Longitude" type="number" step="any" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:button type="button" variant="ghost" wire:click="batalUbahTanah">Batal</flux:button>
                            <flux:button type="submit" variant="primary" icon="check">Simpan Data Tanah</flux:button>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Ringkasan tanah --}}
            <section class="kartu overflow-hidden" aria-labelledby="judul-tanah">
                <div class="space-y-3 p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h2 id="judul-tanah" class="font-semibold text-primary-900">Data Tanah Wakaf</h2>
                        @if ($bisaUbah && $pengajuan->jenis === \App\Enums\JenisPengajuan::Aiw && ! $mengubahTanah)
                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="mulaiUbahTanah">Ubah</flux:button>
                        @endif
                    </div>
                    @php
                        $rincian = [
                            'Nama' => $tanah->nama,
                            'Kode' => $tanah->kode,
                            'Jenis penggunaan' => $tanah->jenis_penggunaan->label(),
                            'Luas' => format_luas($tanah->luas_m2),
                            'Wakif' => $tanah->wakif_nama,
                            'Lokasi' => trim(($tanah->desa?->namaLengkap() ?? '').', '.($tanah->desa?->kecamatan?->nama ?? ''), ', '),
                            'Alamat' => $tanah->alamat,
                            'Status sertifikat' => $tanah->status_sertifikat->label(),
                            'Nomor AIW' => $tanah->nomor_aiw,
                            'Nomor sertifikat' => $tanah->nomor_sertifikat,
                        ];
                    @endphp
                    <dl class="space-y-2 text-sm">
                        @foreach (array_filter($rincian, 'filled') as $label => $nilai)
                            <div class="grid grid-cols-5 gap-2">
                                <dt class="col-span-2 text-zinc-500">{{ $label }}</dt>
                                <dd class="col-span-3 font-medium text-primary-900">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <x-peta mode="data" :data="[$tanah->dataPublik()]" :tombol-detail="false" :scroll-zoom="false" :legenda="false"
                    tinggi="h-56" class="rounded-none border-x-0 border-b-0"
                    wire:key="peta-tanah-{{ $tanah->id }}-{{ $tanah->updated_at?->timestamp }}" />
            </section>

            {{-- Riwayat --}}
            <section class="kartu p-5" aria-labelledby="judul-riwayat">
                <h2 id="judul-riwayat" class="mb-4 flex items-center gap-2 font-semibold text-primary-900">
                    <flux:icon.clock class="size-5 text-primary-700" /> Riwayat
                </h2>
                @if ($riwayats->isEmpty())
                    <p class="text-sm text-zinc-500">Belum ada riwayat. Riwayat tercatat setelah pengajuan dikirim.</p>
                @else
                    <ol class="relative space-y-5 border-s border-primary-100 ps-5">
                        @foreach ($riwayats as $r)
                            <li wire:key="riwayat-{{ $r->id }}" class="relative">
                                <span class="absolute -start-[1.6rem] top-1 size-2.5 rounded-full bg-primary-600 ring-4 ring-white"></span>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                    @if ($r->dari_status)
                                        <x-badge-status :status="$r->dari_status" />
                                        <flux:icon.arrow-right class="size-3 text-zinc-400" />
                                    @endif
                                    <x-badge-status :status="$r->ke_status" />
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">{{ $r->user?->name ?? 'Sistem' }} · {{ $tgl($r->created_at) }}</p>
                                @if (filled($r->catatan))
                                    <p class="mt-1 whitespace-pre-line text-sm text-zinc-700">{{ $r->catatan }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>

    {{-- Konfirmasi kirim --}}
    @if ($bisaUbah)
        <flux:modal name="konfirmasi-kirim" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $status === \App\Enums\StatusPengajuan::PerluPerbaikan ? 'Kirim ulang pengajuan?' : 'Kirim pengajuan?' }}</flux:heading>
                @if ($kurang->isNotEmpty())
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.text>Dokumen wajib belum lengkap: {{ $kurang->pluck('nama')->implode(', ') }}.</flux:callout.text>
                    </flux:callout>
                @elseif ($adaTidakValid)
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.text>Masih ada dokumen yang ditandai tidak valid. Ganti dokumen tersebut terlebih dahulu.</flux:callout.text>
                    </flux:callout>
                @else
                    <flux:text>
                        Pengajuan akan dikirim ke {{ $pengajuan->jenis === \App\Enums\JenisPengajuan::Aiw ? 'KUA kecamatan lokasi tanah' : 'BPN' }}
                        untuk diperiksa. Selama diperiksa, data dan dokumen tidak bisa diubah.
                    </flux:text>
                @endif
                @error('dokumen') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" icon="paper-airplane" wire:click="kirim">Kirim</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
