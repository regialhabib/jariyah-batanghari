<?php

use App\Exports\TemplateImporTanahExport;
use App\Imports\TanahWakafImport;
use App\Models\TanahWakaf;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new #[Title('Impor Data Wakaf')] class extends Component {
    use WithFileUploads;

    public $berkas = null;

    /** @var array{total: int, valid: int, galat: int}|null */
    public ?array $ringkasan = null;

    /** @var list<array{baris: int, nama: string, galat: list<string>}> */
    public array $daftarGalat = [];

    /** @var list<array{baris: int, nama: string}> */
    public array $contohValid = [];

    public ?int $jumlahDiimpor = null;

    public function mount(): void
    {
        $this->authorize('impor', TanahWakaf::class);
    }

    public function updatedBerkas(): void
    {
        $this->reset('ringkasan', 'daftarGalat', 'contohValid', 'jumlahDiimpor');
    }

    protected function rules(): array
    {
        return [
            'berkas' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'berkas.required' => 'Pilih berkas Excel atau CSV terlebih dahulu.',
            'berkas.mimes' => 'Berkas harus berformat .xlsx, .xls, atau .csv.',
            'berkas.extensions' => 'Berkas harus berformat .xlsx, .xls, atau .csv.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ];
    }

    protected function baca(): ?TanahWakafImport
    {
        $import = new TanahWakafImport;

        try {
            Excel::import($import, $this->berkas);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('berkas', 'Berkas tidak bisa dibaca. Pastikan formatnya sesuai template.');

            return null;
        }

        if ($pesan = $import->galatBerkas()) {
            $this->addError('berkas', $pesan);

            return null;
        }

        return $import;
    }

    public function periksa(): void
    {
        $this->authorize('impor', TanahWakaf::class);
        $this->validate();
        $this->reset('ringkasan', 'daftarGalat', 'contohValid', 'jumlahDiimpor');

        if (! $import = $this->baca()) {
            return;
        }

        $valid = $import->barisValid();
        $galat = $import->barisGalat();

        $this->ringkasan = ['total' => count($import->hasil()), 'valid' => count($valid), 'galat' => count($galat)];
        $this->daftarGalat = array_map(fn ($h) => ['baris' => $h['baris'], 'nama' => $h['nama'], 'galat' => $h['galat']], array_slice($galat, 0, 200));
        $this->contohValid = array_map(fn ($h) => ['baris' => $h['baris'], 'nama' => $h['nama']], array_slice($valid, 0, 10));
    }

    public function impor(): void
    {
        $this->authorize('impor', TanahWakaf::class);
        $this->validate();

        // Berkas dibaca & divalidasi ulang di server; hasil pratinjau dari klien tidak dipakai.
        if (! $import = $this->baca()) {
            return;
        }

        if ($import->barisValid() === []) {
            $this->addError('berkas', 'Tidak ada baris valid yang bisa diimpor.');

            return;
        }

        $jumlah = $import->simpan();
        $lewati = count($import->barisGalat());

        activity('tanah_wakaf')->causedBy(auth()->user())
            ->withProperties(['jumlah' => $jumlah, 'dilewati' => $lewati, 'berkas' => $this->berkas->getClientOriginalName()])
            ->log("Impor {$jumlah} data tanah wakaf");

        $this->reset('berkas', 'ringkasan', 'daftarGalat', 'contohValid');
        $this->jumlahDiimpor = $jumlah;

        Flux::toast(variant: 'success', text: "{$jumlah} data tanah wakaf berhasil diimpor".($lewati ? ", {$lewati} baris dilewati." : '.'));
    }

    public function unduhTemplate(): BinaryFileResponse
    {
        $this->authorize('impor', TanahWakaf::class);

        return Excel::download(new TemplateImporTanahExport, 'template-impor-tanah-wakaf.xlsx');
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman judul="Impor Data Wakaf" deskripsi="Unggah data tanah wakaf dari berkas Excel (.xlsx) atau CSV sesuai template."
        :kembali="route('admin.tanah.index')" label-kembali="Kembali ke data wakaf" />

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="kartu space-y-4 p-5 lg:col-span-2">
            <h2 class="font-semibold text-primary-900">1. Unggah berkas</h2>

            <form wire:submit="periksa" class="space-y-4">
                <x-admin.unggah-berkas wire:model="berkas" accept=".xlsx,.xls,.csv" label="Berkas data" keterangan="Format .xlsx, .xls, atau .csv. Maksimal 5 MB." />

                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary" icon="magnifying-glass" wire:loading.attr="disabled" wire:target="berkas,periksa">Periksa Berkas</flux:button>
                </div>
            </form>

            @if ($jumlahDiimpor !== null)
                <flux:callout variant="success" icon="check-circle" heading="{{ $jumlahDiimpor }} data berhasil diimpor.">
                    <flux:callout.text>Data hasil impor langsung dipublikasikan. <a href="{{ route('admin.tanah.index') }}" wire:navigate class="font-medium underline">Lihat Data Wakaf</a></flux:callout.text>
                </flux:callout>
            @endif

            @if ($ringkasan)
                <div class="space-y-4 border-t border-zinc-100 pt-4">
                    <h2 class="font-semibold text-primary-900">2. Hasil pemeriksaan</h2>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-zinc-50 p-3"><p class="text-xs text-zinc-500">Total baris</p><p class="text-xl font-bold text-zinc-800">{{ $ringkasan['total'] }}</p></div>
                        <div class="rounded-xl bg-primary-50 p-3"><p class="text-xs text-zinc-500">Valid</p><p class="text-xl font-bold text-primary-800">{{ $ringkasan['valid'] }}</p></div>
                        <div class="rounded-xl bg-red-50 p-3"><p class="text-xs text-zinc-500">Bermasalah</p><p class="text-xl font-bold text-red-700">{{ $ringkasan['galat'] }}</p></div>
                    </div>

                    @if ($daftarGalat)
                        <div>
                            <p class="mb-2 text-sm font-medium text-red-700">Baris bermasalah (tidak akan diimpor):</p>
                            <ul class="max-h-80 divide-y divide-red-100 overflow-y-auto rounded-xl border border-red-100 bg-red-50/40 text-sm">
                                @foreach ($daftarGalat as $g)
                                    <li class="px-3 py-2" wire:key="galat-{{ $g['baris'] }}">
                                        <p class="font-medium text-zinc-800">Baris {{ $g['baris'] }}{{ $g['nama'] !== '' ? ' · '.$g['nama'] : '' }}</p>
                                        <ul class="mt-0.5 list-disc ps-5 text-red-700">
                                            @foreach ($g['galat'] as $pesan)
                                                <li>{{ $pesan }}</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($contohValid)
                        <div>
                            <p class="mb-2 text-sm font-medium text-primary-800">Contoh baris valid:</p>
                            <ul class="text-sm text-zinc-700">
                                @foreach ($contohValid as $v)
                                    <li wire:key="valid-{{ $v['baris'] }}">Baris {{ $v['baris'] }} · {{ $v['nama'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button variant="primary" icon="arrow-down-on-square-stack" wire:click="impor" :disabled="$ringkasan['valid'] === 0" wire:loading.attr="disabled" wire:target="impor">
                            Impor {{ $ringkasan['valid'] }} Baris Valid
                        </flux:button>
                        @if ($ringkasan['galat'])
                            <flux:text>Perbaiki baris bermasalah lalu unggah ulang, atau lanjutkan impor baris valid saja.</flux:text>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <aside class="kartu space-y-4 p-5">
            <h2 class="font-semibold text-primary-900">Template impor</h2>
            <flux:text>Gunakan template agar judul kolom sesuai. Baris pertama berisi judul kolom, data mulai baris kedua.</flux:text>
            <flux:button icon="arrow-down-tray" wire:click="unduhTemplate" class="w-full">Unduh Template Excel</flux:button>

            <div class="text-sm text-zinc-600">
                <p class="font-medium text-zinc-800">Kolom:</p>
                <p class="mt-1 font-mono text-xs leading-relaxed">{{ implode(', ', TanahWakafImport::KOLOM) }}</p>
                <ul class="mt-3 list-disc space-y-1 ps-5 text-xs">
                    <li>Wajib: {{ implode(', ', TanahWakafImport::KOLOM_WAJIB) }}.</li>
                    <li><strong>kecamatan</strong> &amp; <strong>desa</strong> ditulis sesuai nama wilayah yang terdaftar.</li>
                    <li><strong>jenis_penggunaan</strong>: masjid, mushola, pesantren, sekolah, makam, lainnya.</li>
                    <li><strong>status_sertifikat</strong>: belum, proses, atau sudah (kosong = belum).</li>
                    <li>Tanggal: YYYY-MM-DD atau DD/MM/YYYY. Koordinat memakai titik desimal.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
