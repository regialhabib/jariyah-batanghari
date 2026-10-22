<?php

use App\Enums\JenisPengajuan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusValidasiDokumen;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflow;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Pemeriksaan Pengajuan')] class extends Component {
    use WithFileUploads;

    public Pengajuan $pengajuan;

    // Dokumen tidak valid
    public ?int $dokumenId = null;

    public string $catatanDokumen = '';

    // Minta perbaikan / tolak
    public string $catatanPerbaikan = '';

    public string $alasanTolak = '';

    // Sahkan AIW
    public string $nomorAiw = '';

    public string $tanggalAiw = '';

    public $scanAiw = null;

    public string $catatanAiw = '';

    // Verifikasi sertifikat
    public string $catatanVerifikasi = '';

    // Sertifikat terbit
    public string $nomorSertifikat = '';

    public string $tanggalSertifikat = '';

    public $scanSertifikat = null;

    public string $catatanSertifikat = '';

    public function mount(Pengajuan $pengajuan): void
    {
        $this->authorize('view', $pengajuan);
        $this->pengajuan = $pengajuan;
        $this->tanggalAiw = now()->format('Y-m-d');
        $this->tanggalSertifikat = now()->format('Y-m-d');
    }

    protected function workflow(): PengajuanWorkflow
    {
        return app(PengajuanWorkflow::class);
    }

    protected function pemeriksaan(): void
    {
        $this->authorize('view', $this->pengajuan);
        $this->authorize('periksa', $this->pengajuan);
    }

    protected function selesai(string $pesan, ?string $modal = null): void
    {
        $this->pengajuan->refresh();
        if ($modal) {
            Flux::modal($modal)->close();
        }
        Flux::toast(variant: 'success', text: $pesan);
    }

    public function tandaiValid(int $id): void
    {
        $this->pemeriksaan();
        $dokumen = $this->pengajuan->dokumens()->where('kategori', KategoriDokumen::Persyaratan->value)->findOrFail($id);

        $this->workflow()->validasiDokumen($dokumen, auth()->user(), StatusValidasiDokumen::Valid);

        $this->selesai('Dokumen ditandai valid.');
    }

    public function bukaTidakValid(int $id): void
    {
        $this->pemeriksaan();
        $dokumen = $this->pengajuan->dokumens()->where('kategori', KategoriDokumen::Persyaratan->value)->findOrFail($id);

        $this->dokumenId = $dokumen->id;
        $this->catatanDokumen = (string) $dokumen->catatan;
        $this->resetErrorBag();
        Flux::modal('dokumen-tidak-valid')->show();
    }

    public function tandaiTidakValid(): void
    {
        $this->pemeriksaan();
        $this->validate(['catatanDokumen' => ['required', 'string', 'max:1000']], [], ['catatanDokumen' => 'catatan']);
        $dokumen = $this->pengajuan->dokumens()->where('kategori', KategoriDokumen::Persyaratan->value)->findOrFail((int) $this->dokumenId);

        $this->workflow()->validasiDokumen($dokumen, auth()->user(), StatusValidasiDokumen::TidakValid, $this->catatanDokumen);

        $this->reset('dokumenId', 'catatanDokumen');
        $this->selesai('Dokumen ditandai tidak valid.', 'dokumen-tidak-valid');
    }

    public function mintaPerbaikan(): void
    {
        $this->pemeriksaan();
        $this->validate(['catatanPerbaikan' => ['required', 'string', 'max:2000']], [], ['catatanPerbaikan' => 'catatan perbaikan']);

        $this->workflow()->mintaPerbaikan($this->pengajuan, auth()->user(), $this->catatanPerbaikan);

        $this->reset('catatanPerbaikan');
        $this->selesai('Pengajuan dikembalikan ke Nazir untuk diperbaiki.', 'minta-perbaikan');
    }

    public function tolak(): void
    {
        $this->pemeriksaan();
        $this->validate(['alasanTolak' => ['required', 'string', 'max:2000']], [], ['alasanTolak' => 'alasan penolakan']);

        $this->workflow()->tolak($this->pengajuan, auth()->user(), $this->alasanTolak);

        $this->reset('alasanTolak');
        $this->selesai('Pengajuan ditolak.', 'tolak-pengajuan');
    }

    public function sahkanAiw(): void
    {
        $this->pemeriksaan();
        $this->validate([
            'nomorAiw' => ['required', 'string', 'max:100'],
            'tanggalAiw' => ['required', 'date', 'before_or_equal:today'],
            'scanAiw' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatanAiw' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'nomorAiw' => 'nomor AIW', 'tanggalAiw' => 'tanggal AIW', 'scanAiw' => 'scan AIW', 'catatanAiw' => 'catatan',
        ]);

        $this->workflow()->sahkanAiw(
            $this->pengajuan, auth()->user(), $this->nomorAiw, CarbonImmutable::parse($this->tanggalAiw),
            $this->scanAiw, $this->catatanAiw ?: null,
        );

        $this->reset('nomorAiw', 'scanAiw', 'catatanAiw');
        $this->selesai('Akta Ikrar Wakaf berhasil disahkan.', 'sahkan-aiw');
    }

    public function verifikasi(): void
    {
        $this->pemeriksaan();
        $this->validate(['catatanVerifikasi' => ['nullable', 'string', 'max:2000']], [], ['catatanVerifikasi' => 'catatan']);

        $this->workflow()->verifikasi($this->pengajuan, auth()->user(), $this->catatanVerifikasi ?: null);

        $this->reset('catatanVerifikasi');
        $this->selesai('Berkas sertifikat dinyatakan terverifikasi.', 'verifikasi');
    }

    public function terbitkanSertifikat(): void
    {
        $this->pemeriksaan();
        $this->validate([
            'nomorSertifikat' => ['required', 'string', 'max:100'],
            'tanggalSertifikat' => ['required', 'date', 'before_or_equal:today'],
            'scanSertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatanSertifikat' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'nomorSertifikat' => 'nomor sertifikat', 'tanggalSertifikat' => 'tanggal sertifikat',
            'scanSertifikat' => 'scan sertifikat', 'catatanSertifikat' => 'catatan',
        ]);

        $this->workflow()->terbitkanSertifikat(
            $this->pengajuan, auth()->user(), $this->nomorSertifikat, CarbonImmutable::parse($this->tanggalSertifikat),
            $this->scanSertifikat, $this->catatanSertifikat ?: null,
        );

        $this->reset('nomorSertifikat', 'scanSertifikat', 'catatanSertifikat');
        $this->selesai('Sertifikat wakaf berhasil dicatat terbit.', 'sertifikat-terbit');
    }
}; ?>


<div class="space-y-6">
    @php
        $p = $pengajuan->loadMissing(['tanahWakaf.desa.kecamatan', 'tanahWakaf.nazir', 'nazir', 'pemeriksa', 'dokumens.persyaratan', 'riwayats.user']);
        $tanah = $p->tanahWakaf;
        $user = auth()->user();
        $bisaPeriksa = $user->can('periksa', $p);
        $persyaratan = $p->dokumens->where('kategori', KategoriDokumen::Persyaratan);
        $hasil = $p->dokumens->where('kategori', '!=', KategoriDokumen::Persyaratan);
        $adaTidakValid = $persyaratan->contains(fn ($d) => $d->status_validasi === StatusValidasiDokumen::TidakValid);
        $semuaValid = $persyaratan->isNotEmpty() && $persyaratan->every(fn ($d) => $d->status_validasi === StatusValidasiDokumen::Valid);
        $bisaValidasiDokumen = $bisaPeriksa && $p->status === StatusPengajuan::Diajukan;
        $tgl = fn ($d) => $d?->locale('id')->translatedFormat('j F Y');
        $nik = $tanah->wakif_nik ? str_repeat('•', max(strlen($tanah->wakif_nik) - 4, 0)).substr($tanah->wakif_nik, -4) : null;
        $petaUrl = $user->can('update', $tanah) ? route('admin.tanah.edit', $tanah) : null;
        $galatAlur = collect(['dokumen', 'status', 'catatan'])->flatMap(fn ($k) => $errors->get($k));
    @endphp

    <x-admin.judul-halaman judul="Pemeriksaan Pengajuan" :kembali="route('admin.pengajuan.index')" label-kembali="Kembali ke daftar pengajuan">
        <x-slot:keterangan>
            <span class="font-mono">{{ $p->nomor ?? '-' }}</span> · {{ $p->jenis->label() }}
            @if ($p->diajukan_at) · Diajukan {{ $tgl($p->diajukan_at) }} @endif
        </x-slot:keterangan>
        <x-badge-status :status="$p->status" class="px-3! py-1! text-sm!" />
    </x-admin.judul-halaman>

    @if ($galatAlur->isNotEmpty())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Tindakan tidak dapat diproses">
            @foreach ($galatAlur as $pesan)
                <flux:callout.text>{{ $pesan }}</flux:callout.text>
            @endforeach
        </flux:callout>
    @endif

    @if ($p->catatan_pemeriksa && in_array($p->status, [StatusPengajuan::PerluPerbaikan, StatusPengajuan::Ditolak], true))
        <flux:callout :variant="$p->status === StatusPengajuan::Ditolak ? 'danger' : 'warning'" icon="chat-bubble-left-ellipsis"
            heading="{{ $p->status === StatusPengajuan::Ditolak ? 'Alasan penolakan' : 'Catatan perbaikan' }}">
            <flux:callout.text>{{ $p->catatan_pemeriksa }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Data tanah --}}
            <section class="kartu p-5" aria-labelledby="judul-tanah">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="judul-tanah" class="font-semibold text-primary-900">Data Tanah Wakaf</h2>
                        <p class="text-sm text-zinc-500">{{ $tanah->kode }}</p>
                    </div>
                    <x-badge-status :status="$tanah->status_sertifikat" />
                </div>
                <div class="mt-4 grid gap-5 lg:grid-cols-2">
                    <dl class="divide-y divide-zinc-100 text-sm">
                        @foreach ([
                            'Nama' => $tanah->nama,
                            'Jenis Penggunaan' => $tanah->jenis_penggunaan->label(),
                            'Luas' => format_luas($tanah->luas_m2),
                            'Wakif' => $tanah->wakif_nama,
                            'NIK Wakif' => $nik,
                            'Desa/Kelurahan' => $tanah->desa?->namaLengkap(),
                            'Kecamatan' => $tanah->desa?->kecamatan?->nama,
                            'Alamat' => $tanah->alamat,
                            'Nomor AIW' => $tanah->nomor_aiw,
                            'Tanggal AIW' => $tgl($tanah->tanggal_aiw),
                            'Nomor Sertifikat' => $tanah->nomor_sertifikat,
                            'Tanggal Sertifikat' => $tgl($tanah->tanggal_sertifikat),
                        ] as $label => $nilai)
                            <div class="grid grid-cols-[9rem_1fr] gap-3 py-2">
                                <dt class="text-zinc-500">{{ $label }}</dt>
                                <dd class="font-medium text-zinc-800">{{ filled($nilai) ? $nilai : '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <x-peta mode="data" :data="[[...$tanah->dataPublik(), 'url' => $petaUrl]]" :tombol-detail="(bool) $petaUrl"
                        :scroll-zoom="false" :legenda="false" tinggi="h-72" label="Lokasi {{ $tanah->nama }}" />
                </div>
            </section>

            {{-- Dokumen persyaratan --}}
            <section class="kartu p-5" aria-labelledby="judul-dokumen">
                <h2 id="judul-dokumen" class="font-semibold text-primary-900">Dokumen Persyaratan</h2>
                @if ($bisaValidasiDokumen)
                    <p class="mt-1 text-sm text-zinc-600">Periksa setiap dokumen lalu tandai Valid atau Tidak Valid.</p>
                @endif

                @if ($persyaratan->isEmpty())
                    <x-empty-state icon="document" title="Belum ada dokumen" />
                @else
                    <ul class="mt-3 divide-y divide-zinc-100">
                        @foreach ($persyaratan as $dok)
                            <li wire:key="dok-{{ $dok->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <x-ikon-kotak icon="document-text" size="sm" variant="soft" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-zinc-800">{{ $dok->judul() }}</p>
                                        <a href="{{ $dok->url() }}" target="_blank" rel="noopener" class="text-xs text-primary-700 hover:underline">
                                            {{ $dok->nama_asli }} ({{ number_format($dok->ukuran / 1024, 0, ',', '.') }} KB)
                                        </a>
                                        @if ($dok->catatan)
                                            <p class="mt-0.5 text-xs text-red-700">Catatan: {{ $dok->catatan }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge-status :status="$dok->status_validasi" />
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="$dok->url()" target="_blank" aria-label="Buka {{ $dok->judul() }}" />
                                    @if ($bisaValidasiDokumen)
                                        <flux:button size="sm" icon="check" wire:click="tandaiValid({{ $dok->id }})"
                                            :variant="$dok->status_validasi === StatusValidasiDokumen::Valid ? 'primary' : 'outline'">Valid</flux:button>
                                        <flux:button size="sm" icon="x-mark" wire:click="bukaTidakValid({{ $dok->id }})"
                                            :variant="$dok->status_validasi === StatusValidasiDokumen::TidakValid ? 'danger' : 'outline'">Tidak Valid</flux:button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Dokumen hasil --}}
            @if ($hasil->isNotEmpty())
                <section class="kartu p-5" aria-labelledby="judul-hasil">
                    <h2 id="judul-hasil" class="font-semibold text-primary-900">Dokumen Hasil</h2>
                    <ul class="mt-3 divide-y divide-zinc-100">
                        @foreach ($hasil as $dok)
                            <li wire:key="hasil-{{ $dok->id }}" class="flex items-center justify-between gap-3 py-3">
                                <div>
                                    <p class="font-medium text-zinc-800">{{ $dok->judul() }}</p>
                                    <p class="text-xs text-zinc-500">{{ $dok->nama_asli }}</p>
                                </div>
                                <flux:button size="sm" icon="arrow-down-tray" :href="$dok->url()" target="_blank">Buka</flux:button>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Panel tindakan --}}
            <section class="kartu p-5" aria-labelledby="judul-aksi">
                <h2 id="judul-aksi" class="font-semibold text-primary-900">Tindakan</h2>

                @if ($bisaPeriksa && $p->status === StatusPengajuan::Diajukan)
                    @if ($adaTidakValid)
                        <p class="mt-2 text-sm text-amber-700">Ada dokumen tidak valid. Pilih "Minta Perbaikan" atau "Tolak".</p>
                    @elseif (! $semuaValid)
                        <p class="mt-2 text-sm text-zinc-600">Tandai semua dokumen valid sebelum {{ $p->jenis === JenisPengajuan::Aiw ? 'mengesahkan AIW' : 'memverifikasi' }}.</p>
                    @endif
                    <div class="mt-4 flex flex-col gap-2">
                        @if ($p->jenis === JenisPengajuan::Aiw)
                            <flux:modal.trigger name="sahkan-aiw">
                                <flux:button variant="primary" icon="check-badge" class="w-full" :disabled="! $semuaValid">Sahkan AIW</flux:button>
                            </flux:modal.trigger>
                        @else
                            <flux:modal.trigger name="verifikasi">
                                <flux:button variant="primary" icon="check-badge" class="w-full" :disabled="! $semuaValid">Verifikasi</flux:button>
                            </flux:modal.trigger>
                        @endif
                        <flux:modal.trigger name="minta-perbaikan">
                            <flux:button icon="arrow-uturn-left" class="w-full">Minta Perbaikan</flux:button>
                        </flux:modal.trigger>
                        <flux:modal.trigger name="tolak-pengajuan">
                            <flux:button variant="ghost" icon="x-circle" class="w-full text-red-600! hover:bg-red-50!">Tolak Pengajuan</flux:button>
                        </flux:modal.trigger>
                    </div>
                @elseif ($bisaPeriksa && $p->status === StatusPengajuan::Terverifikasi)
                    <p class="mt-2 text-sm text-zinc-600">Berkas sudah terverifikasi. Catat sertifikat wakaf setelah terbit.</p>
                    <div class="mt-4 flex flex-col gap-2">
                        <flux:modal.trigger name="sertifikat-terbit">
                            <flux:button variant="primary" icon="shield-check" class="w-full">Catat Sertifikat Terbit</flux:button>
                        </flux:modal.trigger>
                        <flux:modal.trigger name="tolak-pengajuan">
                            <flux:button variant="ghost" icon="x-circle" class="w-full text-red-600! hover:bg-red-50!">Tolak Pengajuan</flux:button>
                        </flux:modal.trigger>
                    </div>
                @else
                    <p class="mt-2 text-sm text-zinc-600">
                        @if ($p->status->isFinal())
                            Pengajuan sudah selesai ({{ $p->status->label() }}).
                        @elseif ($p->status === StatusPengajuan::PerluPerbaikan)
                            Menunggu Nazir memperbaiki dan mengirim ulang pengajuan.
                        @elseif (! $bisaPeriksa)
                            Pengajuan ini diperiksa oleh {{ $p->jenis === JenisPengajuan::Aiw ? 'Petugas KUA kecamatan' : 'BPN' }}. Anda hanya dapat melihat.
                        @else
                            Tidak ada tindakan yang tersedia.
                        @endif
                    </p>
                @endif
            </section>

            {{-- Nazir --}}
            <section class="kartu p-5" aria-labelledby="judul-nazir">
                <h2 id="judul-nazir" class="font-semibold text-primary-900">Nazir</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-zinc-500">Nama</dt><dd class="font-medium text-zinc-800">{{ $p->nazir->name }}</dd></div>
                    <div><dt class="text-zinc-500">Email</dt><dd class="font-medium text-zinc-800"><a href="mailto:{{ $p->nazir->email }}" class="hover:underline">{{ $p->nazir->email }}</a></dd></div>
                    <div><dt class="text-zinc-500">No. HP</dt><dd class="font-medium text-zinc-800">{{ $p->nazir->phone ?: '-' }}</dd></div>
                </dl>
                @if ($p->pemeriksa)
                    <p class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500">Pemeriksa terakhir: {{ $p->pemeriksa->name }}</p>
                @endif
            </section>

            {{-- Riwayat --}}
            <section class="kartu p-5" aria-labelledby="judul-riwayat">
                <h2 id="judul-riwayat" class="font-semibold text-primary-900">Riwayat</h2>
                @if ($p->riwayats->isEmpty())
                    <p class="mt-2 text-sm text-zinc-500">Belum ada riwayat.</p>
                @else
                    <ol class="mt-3 space-y-4 border-s border-primary-100 ps-4">
                        @foreach ($p->riwayats as $r)
                            <li wire:key="riwayat-{{ $r->id }}" class="relative">
                                <span class="absolute -start-[1.4rem] top-1 size-3 rounded-full border-2 border-white bg-primary-600"></span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge-status :status="$r->ke_status" />
                                    <span class="text-xs text-zinc-500">{{ $r->created_at?->locale('id')->translatedFormat('j M Y, H:i') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-600">oleh {{ $r->user?->name ?? 'Sistem' }}</p>
                                @if ($r->catatan)
                                    <p class="mt-1 text-sm text-zinc-700">{{ $r->catatan }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>

    {{-- Modal --}}
    <flux:modal name="dokumen-tidak-valid" class="w-full max-w-md">
        <form wire:submit="tandaiTidakValid" class="space-y-4">
            <flux:heading size="lg">Dokumen tidak valid</flux:heading>
            <flux:textarea wire:model="catatanDokumen" label="Catatan untuk Nazir" rows="3" placeholder="Mis. scan buram, tanda tangan tidak ada..." required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Tandai Tidak Valid</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="minta-perbaikan" class="w-full max-w-md">
        <form wire:submit="mintaPerbaikan" class="space-y-4">
            <flux:heading size="lg">Minta perbaikan</flux:heading>
            <flux:text>Pengajuan dikembalikan ke Nazir. Jelaskan apa yang perlu diperbaiki.</flux:text>
            <flux:textarea wire:model="catatanPerbaikan" label="Catatan perbaikan" rows="4" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Kirim ke Nazir</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="tolak-pengajuan" class="w-full max-w-md">
        <form wire:submit="tolak" class="space-y-4">
            <flux:heading size="lg">Tolak pengajuan</flux:heading>
            <flux:text>Penolakan bersifat final. Nazir akan menerima alasan penolakan.</flux:text>
            <flux:textarea wire:model="alasanTolak" label="Alasan penolakan" rows="4" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Tolak Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="sahkan-aiw" class="w-full max-w-lg">
        <form wire:submit="sahkanAiw" class="space-y-4">
            <flux:heading size="lg">Sahkan Akta Ikrar Wakaf</flux:heading>
            <flux:text>Tanah wakaf akan dipublikasikan dengan nomor dan tanggal AIW berikut.</flux:text>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="nomorAiw" label="Nomor AIW" required />
                <flux:input wire:model="tanggalAiw" label="Tanggal AIW" type="date" required />
            </div>
            <x-admin.unggah-berkas wire:model="scanAiw" label="Scan AIW (opsional)" accept=".pdf,.jpg,.jpeg,.png" keterangan="PDF, JPG, atau PNG, maksimal 5 MB." />
            <flux:textarea wire:model="catatanAiw" label="Catatan (opsional)" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="scanAiw,sahkanAiw">Sahkan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="verifikasi" class="w-full max-w-md">
        <form wire:submit="verifikasi" class="space-y-4">
            <flux:heading size="lg">Verifikasi berkas sertifikat</flux:heading>
            <flux:text>Berkas dinyatakan valid dan menunggu penerbitan sertifikat.</flux:text>
            <flux:textarea wire:model="catatanVerifikasi" label="Catatan (opsional)" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Verifikasi</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="sertifikat-terbit" class="w-full max-w-lg">
        <form wire:submit="terbitkanSertifikat" class="space-y-4">
            <flux:heading size="lg">Catat sertifikat terbit</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="nomorSertifikat" label="Nomor sertifikat" required />
                <flux:input wire:model="tanggalSertifikat" label="Tanggal terbit" type="date" required />
            </div>
            <x-admin.unggah-berkas wire:model="scanSertifikat" label="Scan sertifikat (opsional)" accept=".pdf,.jpg,.jpeg,.png" keterangan="PDF, JPG, atau PNG, maksimal 5 MB." />
            <flux:textarea wire:model="catatanSertifikat" label="Catatan (opsional)" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="scanSertifikat,terbitkanSertifikat">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
