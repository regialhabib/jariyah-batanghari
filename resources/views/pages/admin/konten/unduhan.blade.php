<?php

use App\Models\Unduhan;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Kelola Unduhan')] class extends Component {
    use WithFileUploads;

    public const DISK = 'public';

    public const FOLDER = 'unduhan';

    public ?int $editId = null;

    public string $judul = '';

    public string $keterangan = '';

    public int $urutan = 1;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $berkas = null;

    /** Nama file yang sedang tersimpan (saat mengubah). */
    public ?string $fileLama = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    #[Computed]
    public function unduhans()
    {
        return Unduhan::query()->orderBy('urutan')->orderBy('id')->get();
    }

    protected function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'berkas' => [$this->editId ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['berkas' => 'file'];
    }

    protected function messages(): array
    {
        return [
            'berkas.mimes' => 'File harus berformat PDF, DOC, DOCX, XLS, atau XLSX.',
            'berkas.max' => 'Ukuran file maksimal 10 MB.',
        ];
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->urutan = (int) Unduhan::query()->max('urutan') + 1;
        Flux::modal('form-unduhan')->show();
    }

    public function ubah(int $id): void
    {
        $unduhan = Unduhan::query()->findOrFail($id);

        $this->resetForm();
        $this->editId = $unduhan->id;
        $this->judul = $unduhan->judul;
        $this->keterangan = (string) $unduhan->keterangan;
        $this->urutan = $unduhan->urutan;
        $this->fileLama = $unduhan->file ? basename($unduhan->file) : null;
        Flux::modal('form-unduhan')->show();
    }

    public function simpan(): void
    {
        $data = $this->validate();

        $unduhan = $this->editId ? Unduhan::query()->findOrFail($this->editId) : new Unduhan;
        $unduhan->fill([
            'judul' => $data['judul'],
            'keterangan' => filled($data['keterangan']) ? $data['keterangan'] : null,
            'urutan' => $data['urutan'],
        ]);

        if ($this->berkas) {
            $lama = $unduhan->file;
            $unduhan->file = $this->berkas->store(self::FOLDER, self::DISK);
            if ($lama) {
                Storage::disk(self::DISK)->delete($lama);
            }
        }

        $unduhan->save();

        Flux::modal('form-unduhan')->close();
        Flux::toast(variant: 'success', text: $this->editId ? 'Dokumen unduhan diperbarui.' : 'Dokumen unduhan ditambahkan.');
        $this->resetForm();
        unset($this->unduhans);
    }

    /**
     * Lepas file dari entri tanpa menghapus entrinya (tampil "belum tersedia" di publik).
     */
    public function hapusFile(int $id): void
    {
        $unduhan = Unduhan::query()->findOrFail($id);
        if ($unduhan->file) {
            Storage::disk(self::DISK)->delete($unduhan->file);
            $unduhan->update(['file' => null]);
        }

        $this->fileLama = null;
        unset($this->unduhans);
        Flux::toast(text: 'File dihapus.');
    }

    public function hapus(int $id): void
    {
        $unduhan = Unduhan::query()->findOrFail($id);
        if ($unduhan->file) {
            Storage::disk(self::DISK)->delete($unduhan->file);
        }
        $unduhan->delete();

        unset($this->unduhans);
        Flux::toast(text: 'Dokumen unduhan dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'judul', 'keterangan', 'urutan', 'berkas', 'fileLama']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Dokumen Unduhan" aktif="admin.konten.unduhan"
        deskripsi="Formulir dan contoh dokumen di bagian Dokumen Penting halaman Panduan.">
        <flux:button variant="primary" icon="plus" wire:click="tambah">Tambah Dokumen</flux:button>
    </x-admin.konten.nav>

    <div class="kartu overflow-hidden">
        @if ($this->unduhans->isEmpty())
            <x-empty-state icon="arrow-down-tray" title="Belum ada dokumen unduhan" />
        @else
            <flux:table class="[&_td]:px-3 [&_th]:px-3">
                <flux:table.columns>
                    <flux:table.column class="w-16">Urutan</flux:table.column>
                    <flux:table.column>Dokumen</flux:table.column>
                    <flux:table.column>File</flux:table.column>
                    <flux:table.column align="end">Diunduh</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->unduhans as $unduhan)
                        <flux:table.row :key="'unduhan-'.$unduhan->id">
                            <flux:table.cell>{{ $unduhan->urutan }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal">
                                <p class="font-medium text-primary-900">{{ $unduhan->judul }}</p>
                                @if ($unduhan->keterangan)
                                    <p class="text-xs text-zinc-500">{{ $unduhan->keterangan }}</p>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($unduhan->tersedia())
                                    <a href="{{ $unduhan->url() }}" class="inline-flex items-center gap-1 text-sm text-primary-700 hover:underline" target="_blank">
                                        <flux:icon.document-arrow-down variant="micro" class="size-4" />
                                        {{ strtoupper(pathinfo($unduhan->file, PATHINFO_EXTENSION)) }}
                                    </a>
                                @else
                                    <flux:badge size="sm" color="amber">Belum ada file</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($unduhan->jumlah_unduh, 0, ',', '.') }}×</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="ubah({{ $unduhan->id }})" aria-label="Ubah" />
                                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600!" wire:click="hapus({{ $unduhan->id }})"
                                        wire:confirm="Hapus dokumen &quot;{{ $unduhan->judul }}&quot; beserta filenya?" aria-label="Hapus" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="form-unduhan" class="w-full max-w-lg">
        <form wire:submit="simpan" class="space-y-5">
            <flux:heading size="lg">{{ $editId ? 'Ubah Dokumen' : 'Tambah Dokumen' }}</flux:heading>
            <flux:input wire:model="judul" label="Judul" required />
            <flux:input wire:model="keterangan" label="Keterangan (opsional)" />
            <flux:input type="number" min="0" max="999" wire:model="urutan" label="Urutan" required />

            <flux:field>
                <flux:label>{{ $editId ? 'Ganti File (opsional)' : 'File' }}</flux:label>
                @if ($fileLama)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm">
                        <span class="truncate">File saat ini: {{ $fileLama }}</span>
                        <flux:button size="xs" variant="ghost" class="text-red-600!" wire:click="hapusFile({{ $editId }})"
                            wire:confirm="Hapus file ini? Entri tetap ada dan tampil sebagai belum tersedia.">Hapus file</flux:button>
                    </div>
                @endif
                <input type="file" wire:model="berkas" accept=".pdf,.doc,.docx,.xls,.xlsx"
                    class="block w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                <flux:description>PDF, DOC, DOCX, XLS, atau XLSX. Maksimal 10 MB.</flux:description>
                <div wire:loading wire:target="berkas" class="text-xs text-zinc-500">Mengunggah…</div>
                <flux:error name="berkas" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="berkas,simpan">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
