<?php

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Services\PengajuanWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Wizard pengajuan Nazir (docs/03-alur-pengajuan.md).
 * AIW: 1 Jenis → 2 Data Tanah → 3 Lokasi → 4 Ringkasan. Sertifikat: 1 Jenis & Tanah → 4 Ringkasan.
 * Setelah draf dibuat, Nazir diarahkan ke detail pengajuan untuk mengunggah dokumen.
 */
new #[Title('Buat Pengajuan')] class extends Component {
    public int $langkah = 1;

    public string $jenis = '';

    public ?int $tanah_wakaf_id = null;

    // Data tanah (khusus AIW)
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

    public function mount(): void
    {
        $this->authorize('create', Pengajuan::class);

        $jenis = JenisPengajuan::tryFrom((string) request()->query('jenis', ''));
        if ($jenis) {
            $this->jenis = $jenis->value;
        }

        $tanahId = filter_var(request()->query('tanah'), FILTER_VALIDATE_INT);
        if ($jenis?->isSertifikat() && $tanahId && $this->tanahLayak()->contains('id', $tanahId)) {
            $this->tanah_wakaf_id = $tanahId;
        }
    }

    public function pilihJenis(string $jenis): void
    {
        $this->jenis = JenisPengajuan::tryFrom($jenis)?->value ?? '';
        $this->resetErrorBag();
    }

    public function updatedKecamatanId(): void
    {
        $this->desa_id = '';
    }

    public function lanjut(): void
    {
        if ($this->langkah === 4) {
            $this->simpan(app(PengajuanWorkflow::class));

            return;
        }

        $this->langkah = match ($this->langkah) {
            1 => $this->validasiLangkahSatu(),
            2 => $this->validasiDataTanah(),
            3 => $this->validasiLokasi(),
            default => $this->langkah,
        };
    }

    public function kembali(): void
    {
        $this->resetErrorBag();
        $this->langkah = match ($this->langkah) {
            4 => $this->jenisEnum() === JenisPengajuan::Aiw ? 3 : 1,
            3 => 2,
            default => 1,
        };
    }

    public function keLangkah(int $langkah): void
    {
        // Hanya boleh mundur ke langkah yang sudah dilewati.
        if ($langkah >= 1 && $langkah < $this->langkah) {
            $this->resetErrorBag();
            $this->langkah = $langkah;
        }
    }

    public function simpan(PengajuanWorkflow $workflow): void
    {
        $this->authorize('create', Pengajuan::class);

        $this->validasiLangkahSatu();
        $jenis = $this->jenisEnum();

        if ($jenis === JenisPengajuan::Aiw) {
            $this->validasiDataTanah();
            $this->validasiLokasi();

            $pengajuan = $workflow->buatDraf(auth()->user(), $jenis, dataTanah: [
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
        } else {
            // ID dari klien diambil ulang dengan scope milik Nazir; service memeriksa kelayakan sekali lagi.
            $tanah = TanahWakaf::query()->forUser(auth()->user())->find($this->tanah_wakaf_id);
            $pengajuan = $workflow->buatDraf(auth()->user(), $jenis, $tanah);
        }

        session()->flash('sukses', 'Draf pengajuan berhasil dibuat. Silakan unggah dokumen persyaratan, lalu kirim pengajuan.');

        $this->redirectRoute('nazir.pengajuan.show', $pengajuan, navigate: true);
    }

    protected function validasiLangkahSatu(): int
    {
        $this->validate(
            ['jenis' => ['required', Rule::enum(JenisPengajuan::class)]],
            ['jenis.required' => 'Pilih jenis pengajuan terlebih dahulu.'],
        );

        if ($this->jenisEnum() === JenisPengajuan::Aiw) {
            $this->tanah_wakaf_id = null;

            return 2;
        }

        $this->validate(
            ['tanah_wakaf_id' => ['required', 'integer', Rule::in($this->tanahLayak()->pluck('id')->all())]],
            [
                'tanah_wakaf_id.required' => 'Pilih tanah wakaf yang akan diajukan sertifikatnya.',
                'tanah_wakaf_id.in' => 'Tanah ini tidak bisa diajukan: harus milik Anda, sudah ber-AIW, belum bersertifikat, dan tidak sedang diproses.',
            ],
        );

        return 4;
    }

    protected function validasiDataTanah(): int
    {
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
        ], [], $this->atributTanah());

        return 3;
    }

    protected function validasiLokasi(): int
    {
        $this->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'batas_geojson' => ['nullable', 'array'],
            'batas_geojson.type' => ['required_with:batas_geojson', 'in:Polygon,MultiPolygon'],
            'batas_geojson.coordinates' => ['required_with:batas_geojson', 'array'],
        ], [
            'latitude.required' => 'Tentukan titik lokasi tanah di peta atau isi koordinat secara manual.',
            'longitude.required' => 'Tentukan titik lokasi tanah di peta atau isi koordinat secara manual.',
        ], ['batas_geojson' => 'batas bidang']);

        return 4;
    }

    /**
     * @return array<string, string>
     */
    protected function atributTanah(): array
    {
        return [
            'nama' => 'nama tanah wakaf',
            'jenis_penggunaan' => 'jenis penggunaan',
            'wakif_nama' => 'nama wakif',
            'wakif_nik' => 'NIK wakif',
            'kecamatan_id' => 'kecamatan',
            'desa_id' => 'desa/kelurahan',
            'luas_m2' => 'luas',
        ];
    }

    protected function jenisEnum(): ?JenisPengajuan
    {
        return JenisPengajuan::tryFrom($this->jenis);
    }

    /**
     * Tanah milik Nazir yang bisa diajukan sertifikatnya: sudah ber-AIW, belum bersertifikat,
     * dan tidak punya pengajuan sertifikat yang sedang diproses.
     *
     * @return Collection<int, TanahWakaf>
     */
    #[Computed]
    public function tanahLayak(): Collection
    {
        $aktif = collect(StatusPengajuan::cases())->filter->isAktif()->map->value->values()->all();
        $jenisSertifikat = [JenisPengajuan::SertifikatBersertifikat->value, JenisPengajuan::SertifikatBelum->value];

        return TanahWakaf::query()
            ->forUser(auth()->user())
            ->whereNotNull('nomor_aiw')
            ->where('nomor_aiw', '!=', '')
            ->where('status_sertifikat', '!=', StatusSertifikat::Sudah->value)
            ->whereDoesntHave('pengajuans', fn (Builder $q) => $q->whereIn('jenis', $jenisSertifikat)->whereIn('status', $aktif))
            ->with('desa.kecamatan')
            ->orderBy('nama')
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

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarLangkah(): array
    {
        return $this->jenisEnum()?->isSertifikat()
            ? [1 => 'Jenis & Tanah', 4 => 'Ringkasan']
            : [1 => 'Jenis Pengajuan', 2 => 'Data Tanah', 3 => 'Lokasi', 4 => 'Ringkasan'];
    }
}; ?>

@php
    $jenisDipilih = \App\Enums\JenisPengajuan::tryFrom($jenis);
    $penjelasan = [
        'aiw' => ['document-text', 'Untuk tanah yang baru diwakafkan. Berkas diperiksa KUA kecamatan lokasi tanah, lalu Akta Ikrar Wakaf disahkan.'],
        'sertifikat_bersertifikat' => ['shield-check', 'Untuk tanah ber-AIW yang sudah punya sertifikat hak milik. Berkas diperiksa BPN.'],
        'sertifikat_belum' => ['clipboard-document-check', 'Untuk tanah ber-AIW yang belum punya sertifikat hak. Berkas diperiksa BPN.'],
    ];
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Buat Pengajuan</flux:heading>
            <flux:text class="mt-1">Lengkapi data pengajuan, lalu unggah dokumen persyaratan pada langkah berikutnya.</flux:text>
        </div>
        <flux:button :href="route('nazir.pengajuan.index')" wire:navigate variant="ghost" icon="arrow-left">Kembali</flux:button>
    </div>

    {{-- Penanda langkah --}}
    <ol class="kartu flex flex-wrap items-center gap-x-2 gap-y-3 p-4" aria-label="Langkah pengajuan">
        @foreach ($this->daftarLangkah as $no => $judul)
            @php $urutan = $loop->iteration; $aktif = $langkah === $no; $selesai = $langkah > $no; @endphp
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span class="hidden h-px w-6 bg-primary-200 sm:block" aria-hidden="true"></span>
                @endif
                <button type="button" wire:click="keLangkah({{ $no }})" @disabled(! $selesai)
                    class="flex items-center gap-2 text-sm {{ $aktif ? 'font-semibold text-primary-900' : ($selesai ? 'text-primary-700 hover:underline' : 'text-zinc-500') }}"
                    @if ($aktif) aria-current="step" @endif>
                    <span @class([
                        'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                        'bg-primary-700 text-white' => $aktif,
                        'bg-primary-100 text-primary-700' => $selesai,
                        'bg-zinc-100 text-zinc-500' => ! $aktif && ! $selesai,
                    ])>
                        @if ($selesai) <flux:icon.check class="size-4" /> @else {{ $urutan }} @endif
                    </span>
                    {{ $judul }}
                </button>
            </li>
        @endforeach
        <li class="flex items-center gap-2 text-sm text-zinc-400">
            <span class="hidden h-px w-6 bg-primary-200 sm:block" aria-hidden="true"></span>
            <span class="flex size-7 items-center justify-center rounded-full border border-dashed border-zinc-300 text-xs">{{ count($this->daftarLangkah) + 1 }}</span>
            Unggah Dokumen
        </li>
    </ol>

    @if ($errors->hasAny(['status', 'dokumen']))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Pengajuan belum bisa dibuat</flux:callout.heading>
            <flux:callout.text>{{ $errors->first('status') ?: $errors->first('dokumen') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="lanjut" class="kartu space-y-6 p-5 sm:p-6">
        {{-- Langkah 1: jenis --}}
        @if ($langkah === 1)
            <fieldset class="space-y-3">
                <legend class="mb-3 font-semibold text-primary-900">Pilih jenis pengajuan</legend>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach (\App\Enums\JenisPengajuan::cases() as $kasus)
                        @php [$ikon, $teks] = $penjelasan[$kasus->value]; $dipilih = $jenis === $kasus->value; @endphp
                        <button type="button" wire:click="pilihJenis('{{ $kasus->value }}')" aria-pressed="{{ $dipilih ? 'true' : 'false' }}"
                            @class([
                                'flex h-full flex-col gap-3 rounded-xl border-2 p-4 text-start transition',
                                'border-primary-700 bg-primary-50' => $dipilih,
                                'border-zinc-200 hover:border-primary-300 hover:bg-primary-50/40' => ! $dipilih,
                            ])>
                            <div class="flex items-start justify-between gap-2">
                                <x-ikon-kotak :icon="$ikon" size="sm" :variant="$dipilih ? 'solid' : 'soft'" />
                                @if ($dipilih)
                                    <flux:icon.check-circle variant="solid" class="size-5 text-primary-700" />
                                @endif
                            </div>
                            <span class="font-semibold text-primary-900">{{ $kasus->label() }}</span>
                            <span class="text-sm text-zinc-600">{{ $teks }}</span>
                        </button>
                    @endforeach
                </div>
                <flux:error name="jenis" />
            </fieldset>

            @if ($jenisDipilih?->isSertifikat())
                <div class="space-y-3">
                    <p class="font-semibold text-primary-900">Pilih tanah wakaf</p>
                    @if ($this->tanahLayak->isEmpty())
                        <flux:callout variant="warning" icon="information-circle">
                            <flux:callout.heading>Belum ada tanah yang bisa diajukan sertifikatnya</flux:callout.heading>
                            <flux:callout.text>
                                Pengajuan sertifikat hanya untuk tanah milik Anda yang Akta Ikrar Wakaf-nya sudah disahkan,
                                belum bersertifikat, dan tidak sedang diproses. Ajukan pembuatan AIW terlebih dahulu.
                            </flux:callout.text>
                        </flux:callout>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($this->tanahLayak as $tanah)
                                <label wire:key="tanah-{{ $tanah->id }}" @class([
                                    'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                                    'border-primary-700 bg-primary-50' => (int) $tanah_wakaf_id === $tanah->id,
                                    'border-zinc-200 hover:border-primary-300' => (int) $tanah_wakaf_id !== $tanah->id,
                                ])>
                                    <input type="radio" wire:model.live="tanah_wakaf_id" value="{{ $tanah->id }}" class="mt-1 accent-primary-700">
                                    <span class="min-w-0 text-sm">
                                        <span class="block font-semibold text-primary-900">{{ $tanah->nama }}</span>
                                        <span class="block text-zinc-600">{{ $tanah->kode }} · {{ format_luas($tanah->luas_m2) }}</span>
                                        <span class="block text-zinc-600">{{ $tanah->desa?->nama }}, {{ $tanah->desa?->kecamatan?->nama }}</span>
                                        <span class="block text-zinc-500">AIW: {{ $tanah->nomor_aiw }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <flux:error name="tanah_wakaf_id" />
                </div>
            @endif
        @endif

        {{-- Langkah 2: data tanah (AIW) --}}
        @if ($langkah === 2)
            <div>
                <h2 class="font-semibold text-primary-900">Data tanah wakaf</h2>
                <p class="text-sm text-zinc-600">Isi sesuai dokumen asal tanah dan data wakif.</p>
            </div>
            <x-nazir.form-tanah :kecamatans="$this->kecamatans" :desas="$this->desas" />
        @endif

        {{-- Langkah 3: lokasi (AIW) --}}
        @if ($langkah === 3)
            <div>
                <h2 class="font-semibold text-primary-900">Lokasi tanah wakaf</h2>
                <p class="text-sm text-zinc-600">Titik lokasi wajib diisi. Batas bidang (polygon) boleh digambar jika diketahui.</p>
            </div>

            <flux:field>
                <flux:label>Titik lokasi dan batas bidang</flux:label>
                <x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" tinggi="h-80 sm:h-96" />
                <flux:error name="latitude" />
                <flux:error name="batas_geojson" />
            </flux:field>

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="latitude" label="Latitude" type="number" step="any" placeholder="mis. -1.7012345" />
                <flux:input wire:model="longitude" label="Longitude" type="number" step="any" placeholder="mis. 103.2712345" />
            </div>
        @endif

        {{-- Langkah 4: ringkasan --}}
        @if ($langkah === 4)
            <div>
                <h2 class="font-semibold text-primary-900">Ringkasan pengajuan</h2>
                <p class="text-sm text-zinc-600">Periksa kembali data berikut. Setelah draf disimpan, Anda akan diarahkan untuk mengunggah dokumen.</p>
            </div>

            @php
                if ($jenisDipilih === \App\Enums\JenisPengajuan::Aiw) {
                    $rincian = [
                        'Jenis pengajuan' => $jenisDipilih->label(),
                        'Nama tanah wakaf' => $nama,
                        'Jenis penggunaan' => \App\Enums\JenisPenggunaan::tryFrom($jenis_penggunaan)?->label(),
                        'Luas' => format_luas($luas_m2),
                        'Nama wakif' => $wakif_nama,
                        'NIK wakif' => $wakif_nik ?: '-',
                        'Kecamatan' => $this->kecamatans[(int) $kecamatan_id] ?? '-',
                        'Desa/Kelurahan' => $this->desas[(int) $desa_id] ?? '-',
                        'Alamat' => $alamat ?: '-',
                        'Koordinat' => $latitude !== null ? number_format($latitude, 6, '.', '').', '.number_format((float) $longitude, 6, '.', '') : '-',
                        'Batas bidang' => $batas_geojson ? 'Sudah digambar' : 'Tidak ada',
                    ];
                } else {
                    $t = $this->tanahLayak->firstWhere('id', (int) $tanah_wakaf_id);
                    $rincian = [
                        'Jenis pengajuan' => $jenisDipilih?->label(),
                        'Tanah wakaf' => $t ? $t->nama.' ('.$t->kode.')' : '-',
                        'Lokasi' => $t ? $t->desa?->nama.', '.$t->desa?->kecamatan?->nama : '-',
                        'Luas' => $t ? format_luas($t->luas_m2) : '-',
                        'Nomor AIW' => $t?->nomor_aiw ?? '-',
                    ];
                }
            @endphp

            <dl class="divide-y divide-primary-100 rounded-xl border border-primary-100">
                @foreach ($rincian as $label => $nilai)
                    <div class="grid gap-1 px-4 py-2.5 text-sm sm:grid-cols-3">
                        <dt class="text-zinc-600">{{ $label }}</dt>
                        <dd class="font-medium text-primary-900 sm:col-span-2">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            <flux:callout icon="information-circle" color="sky">
                <flux:callout.text>
                    Pengajuan disimpan sebagai <strong>Draf</strong> dan belum dikirim ke pemeriksa. Anda bisa mengirimnya
                    setelah semua dokumen wajib diunggah.
                </flux:callout.text>
            </flux:callout>

            <flux:error name="tanah_wakaf_id" />
        @endif

        <flux:separator />

        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($langkah > 1)
                <flux:button type="button" wire:click="kembali" variant="ghost" icon="arrow-left">Sebelumnya</flux:button>
            @else
                <span></span>
            @endif

            @if ($langkah === 4)
                <flux:button type="submit" variant="primary" icon="check">Simpan Draf &amp; Unggah Dokumen</flux:button>
            @else
                <flux:button type="submit" variant="primary" icon:trailing="arrow-right">Lanjut</flux:button>
            @endif
        </div>
    </form>
</div>
