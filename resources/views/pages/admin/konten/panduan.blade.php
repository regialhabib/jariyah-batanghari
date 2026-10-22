<?php

use App\Models\PanduanLangkah;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Panduan')] class extends Component {
    public ?int $editId = null;

    public string $judul = '';

    public string $deskripsi = '';

    public int $urutan = 1;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    #[Computed]
    public function langkahs()
    {
        return PanduanLangkah::query()->orderBy('urutan')->orderBy('id')->get();
    }

    protected function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->urutan = (int) PanduanLangkah::query()->max('urutan') + 1;
        Flux::modal('form-panduan')->show();
    }

    public function ubah(int $id): void
    {
        $langkah = PanduanLangkah::query()->findOrFail($id);

        $this->resetForm();
        $this->editId = $langkah->id;
        $this->judul = $langkah->judul;
        $this->deskripsi = $langkah->deskripsi;
        $this->urutan = $langkah->urutan;
        Flux::modal('form-panduan')->show();
    }

    public function simpan(): void
    {
        $data = $this->validate();

        $langkah = $this->editId ? PanduanLangkah::query()->findOrFail($this->editId) : new PanduanLangkah;
        $langkah->fill($data)->save();

        Flux::modal('form-panduan')->close();
        Flux::toast(variant: 'success', text: $this->editId ? 'Langkah panduan diperbarui.' : 'Langkah panduan ditambahkan.');
        $this->resetForm();
        unset($this->langkahs);
    }

    public function hapus(int $id): void
    {
        PanduanLangkah::query()->findOrFail($id)->delete();
        unset($this->langkahs);
        Flux::toast(text: 'Langkah panduan dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'judul', 'deskripsi', 'urutan']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Panduan Pengurusan" aktif="admin.konten.panduan"
        deskripsi="Langkah bernomor yang tampil di halaman Panduan.">
        <flux:button variant="primary" icon="plus" wire:click="tambah">Tambah Langkah</flux:button>
    </x-admin.konten.nav>

    <div class="kartu overflow-hidden">
        @forelse ($this->langkahs as $langkah)
            <div wire:key="langkah-{{ $langkah->id }}" class="flex items-start gap-4 border-b border-primary-50 px-5 py-4 last:border-b-0">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">{{ $loop->iteration }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-primary-900">{{ $langkah->judul }}</p>
                    <p class="text-sm whitespace-pre-line text-zinc-600">{{ $langkah->deskripsi }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Urutan: {{ $langkah->urutan }}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="ubah({{ $langkah->id }})" aria-label="Ubah" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600!" wire:click="hapus({{ $langkah->id }})"
                        wire:confirm="Hapus langkah &quot;{{ $langkah->judul }}&quot;?" aria-label="Hapus" />
                </div>
            </div>
        @empty
            <x-empty-state icon="map" title="Belum ada langkah panduan" />
        @endforelse
    </div>

    <flux:modal name="form-panduan" class="w-full max-w-lg">
        <form wire:submit="simpan" class="space-y-5">
            <flux:heading size="lg">{{ $editId ? 'Ubah Langkah' : 'Tambah Langkah' }}</flux:heading>
            <flux:input wire:model="judul" label="Judul Langkah" required />
            <flux:textarea wire:model="deskripsi" label="Deskripsi" rows="4" required />
            <flux:input type="number" min="0" max="999" wire:model="urutan" label="Urutan" required />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
