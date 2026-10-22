<?php

use App\Enums\JenisPengajuan;
use App\Models\PengajuanDokumen;
use App\Models\Persyaratan;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Persyaratan')] class extends Component {
    public ?int $editId = null;

    public string $jenis_pengajuan = '';

    public string $nama = '';

    public string $keterangan = '';

    public bool $wajib = true;

    public int $urutan = 1;

    /** Persyaratan yang gagal dihapus karena sudah dipakai dokumen pengajuan. */
    public ?int $terpakaiId = null;

    public int $jumlahTerpakai = 0;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Persyaratan>>
     */
    #[Computed]
    public function grup(): array
    {
        $semua = Persyaratan::query()->orderBy('urutan')->orderBy('id')->get()->groupBy(fn (Persyaratan $p) => $p->jenis_pengajuan->value);
        $terpakai = PengajuanDokumen::query()->whereNotNull('persyaratan_id')
            ->selectRaw('persyaratan_id, count(*) as jumlah')->groupBy('persyaratan_id')->pluck('jumlah', 'persyaratan_id');

        return collect(JenisPengajuan::cases())->mapWithKeys(fn (JenisPengajuan $j) => [
            $j->value => ($semua[$j->value] ?? collect())->each(fn (Persyaratan $p) => $p->setAttribute('jumlah_dokumen', (int) ($terpakai[$p->id] ?? 0))),
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'jenis_pengajuan' => ['required', Rule::enum(JenisPengajuan::class)],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'wajib' => ['boolean'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['jenis_pengajuan' => 'jenis pengajuan'];
    }

    public function tambah(string $jenis): void
    {
        $jenisEnum = JenisPengajuan::tryFrom($jenis) ?? JenisPengajuan::Aiw;

        $this->resetForm();
        $this->jenis_pengajuan = $jenisEnum->value;
        $this->urutan = (int) Persyaratan::query()->where('jenis_pengajuan', $jenisEnum->value)->max('urutan') + 1;
        Flux::modal('form-persyaratan')->show();
    }

    public function ubah(int $id): void
    {
        $p = Persyaratan::query()->findOrFail($id);

        $this->resetForm();
        $this->editId = $p->id;
        $this->jenis_pengajuan = $p->jenis_pengajuan->value;
        $this->nama = $p->nama;
        $this->keterangan = (string) $p->keterangan;
        $this->wajib = $p->wajib;
        $this->urutan = $p->urutan;
        Flux::modal('form-persyaratan')->show();
    }

    public function simpan(): void
    {
        $data = $this->validate();
        $data['keterangan'] = filled($data['keterangan']) ? $data['keterangan'] : null;

        $p = $this->editId ? Persyaratan::query()->findOrFail($this->editId) : new Persyaratan;
        $p->fill($data)->save();

        Flux::modal('form-persyaratan')->close();
        Flux::toast(variant: 'success', text: $this->editId ? 'Persyaratan diperbarui.' : 'Persyaratan ditambahkan.');
        $this->resetForm();
        unset($this->grup);
    }

    public function hapus(int $id): void
    {
        $p = Persyaratan::query()->findOrFail($id);
        $jumlah = PengajuanDokumen::query()->where('persyaratan_id', $p->id)->count();

        if ($jumlah > 0) {
            // Dokumen yang sudah diunggah merujuk persyaratan ini: jangan dihapus.
            $this->terpakaiId = $p->id;
            $this->jumlahTerpakai = $jumlah;
            Flux::modal('persyaratan-terpakai')->show();

            return;
        }

        $p->delete();
        unset($this->grup);
        Flux::toast(text: 'Persyaratan dihapus.');
    }

    /**
     * Alternatif hapus: pertahankan persyaratan tetapi jadikan tidak wajib.
     */
    public function jadikanOpsional(): void
    {
        $p = Persyaratan::query()->findOrFail($this->terpakaiId);
        $p->update(['wajib' => false]);

        $this->reset(['terpakaiId', 'jumlahTerpakai']);
        Flux::modal('persyaratan-terpakai')->close();
        unset($this->grup);
        Flux::toast(variant: 'success', text: 'Persyaratan dipertahankan sebagai dokumen tidak wajib.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'jenis_pengajuan', 'nama', 'keterangan', 'wajib', 'urutan']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Persyaratan Pengajuan" aktif="admin.konten.persyaratan"
        deskripsi="Daftar dokumen yang wajib/boleh diunggah Nazir untuk tiap jenis pengajuan." />

    <flux:callout icon="exclamation-triangle" color="amber">
        <flux:callout.text>
            Perubahan persyaratan berlaku untuk pengajuan <strong>baru</strong> dan pengajuan yang masih draf/perlu perbaikan.
            Persyaratan yang sudah dipakai dokumen unggahan tidak dapat dihapus; jadikan tidak wajib bila tidak diperlukan lagi.
        </flux:callout.text>
    </flux:callout>

    @foreach (\App\Enums\JenisPengajuan::cases() as $jenis)
        <section class="kartu overflow-hidden" wire:key="grup-{{ $jenis->value }}">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-primary-100 px-5 py-3">
                <div>
                    <flux:heading size="lg">{{ $jenis->label() }}</flux:heading>
                    <flux:text class="text-xs">{{ $this->grup[$jenis->value]->count() }} persyaratan</flux:text>
                </div>
                <flux:button size="sm" icon="plus" wire:click="tambah('{{ $jenis->value }}')">Tambah</flux:button>
            </div>

            @if ($this->grup[$jenis->value]->isEmpty())
                <x-empty-state icon="clipboard-document-check" title="Belum ada persyaratan" />
            @else
                <ul class="divide-y divide-primary-50">
                    @foreach ($this->grup[$jenis->value] as $p)
                        <li wire:key="persyaratan-{{ $p->id }}" class="flex items-start gap-3 px-5 py-3">
                            <span class="mt-0.5 w-8 shrink-0 text-sm font-semibold text-zinc-400">{{ $p->urutan }}.</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-primary-900">
                                    {{ $p->nama }}
                                    @if ($p->wajib)
                                        <flux:badge size="sm" color="green" class="ms-1">Wajib</flux:badge>
                                    @else
                                        <flux:badge size="sm" class="ms-1">Opsional</flux:badge>
                                    @endif
                                </p>
                                @if ($p->keterangan)
                                    <p class="text-sm text-zinc-600">{{ $p->keterangan }}</p>
                                @endif
                                @if ($p->jumlah_dokumen > 0)
                                    <p class="mt-0.5 text-xs text-zinc-500">Dipakai {{ $p->jumlah_dokumen }} dokumen unggahan</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" tooltip="Ubah" wire:click="ubah({{ $p->id }})" aria-label="Ubah" />
                                <flux:button size="sm" variant="ghost" icon="trash" tooltip="Hapus" class="text-zinc-400! hover:bg-red-50! hover:text-red-600!" wire:click="hapus({{ $p->id }})"
                                    wire:confirm="Hapus persyaratan &quot;{{ $p->nama }}&quot;?" aria-label="Hapus" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    <flux:modal name="form-persyaratan" class="w-full max-w-lg">
        <form wire:submit="simpan" class="space-y-5">
            <flux:heading size="lg">{{ $editId ? 'Ubah Persyaratan' : 'Tambah Persyaratan' }}</flux:heading>

            <flux:select wire:model="jenis_pengajuan" label="Jenis Pengajuan" required>
                @foreach (\App\Enums\JenisPengajuan::cases() as $jenis)
                    <flux:select.option value="{{ $jenis->value }}">{{ $jenis->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="nama" label="Nama Dokumen" required />
            <flux:textarea wire:model="keterangan" label="Keterangan (opsional)" rows="2" />
            <div class="grid items-end gap-4 sm:grid-cols-2">
                <flux:input type="number" min="0" max="999" wire:model="urutan" label="Urutan" required />
                <flux:switch wire:model="wajib" label="Wajib diunggah" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="persyaratan-terpakai" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Persyaratan tidak dapat dihapus</flux:heading>
            <flux:text>
                Persyaratan ini sudah dipakai oleh {{ $jumlahTerpakai }} dokumen unggahan Nazir. Menghapusnya akan memutus
                kaitan dokumen tersebut. Sebagai gantinya, Anda dapat mempertahankannya sebagai dokumen <strong>tidak wajib</strong>.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Tetap Simpan</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="jadikanOpsional">Jadikan Tidak Wajib</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
