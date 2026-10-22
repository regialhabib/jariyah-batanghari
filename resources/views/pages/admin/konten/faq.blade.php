<?php

use App\Models\Faq;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola FAQ')] class extends Component {
    public ?int $editId = null;

    public string $pertanyaan = '';

    public string $jawaban = '';

    public int $urutan = 1;

    public bool $is_published = true;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()->orderBy('urutan')->orderBy('id')->get();
    }

    protected function rules(): array
    {
        return [
            'pertanyaan' => ['required', 'string', 'max:255'],
            'jawaban' => ['required', 'string', 'max:3000'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
        ];
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->urutan = (int) Faq::query()->max('urutan') + 1;
        Flux::modal('form-faq')->show();
    }

    public function ubah(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);

        $this->resetForm();
        $this->editId = $faq->id;
        $this->pertanyaan = $faq->pertanyaan;
        $this->jawaban = $faq->jawaban;
        $this->urutan = $faq->urutan;
        $this->is_published = $faq->is_published;
        Flux::modal('form-faq')->show();
    }

    public function simpan(): void
    {
        $data = $this->validate();

        $faq = $this->editId ? Faq::query()->findOrFail($this->editId) : new Faq;
        $faq->fill($data)->save();

        Flux::modal('form-faq')->close();
        Flux::toast(variant: 'success', text: $this->editId ? 'FAQ diperbarui.' : 'FAQ ditambahkan.');
        $this->resetForm();
        unset($this->faqs);
    }

    public function togglePublish(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);
        $faq->update(['is_published' => ! $faq->is_published]);
        unset($this->faqs);
    }

    public function hapus(int $id): void
    {
        Faq::query()->findOrFail($id)->delete();
        unset($this->faqs);
        Flux::toast(text: 'FAQ dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'pertanyaan', 'jawaban', 'urutan', 'is_published']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Tanya Jawab (FAQ)" aktif="admin.konten.faq"
        deskripsi="Pertanyaan yang sering diajukan, tampil di halaman FAQ.">
        <flux:button variant="primary" icon="plus" wire:click="tambah">Tambah FAQ</flux:button>
    </x-admin.konten.nav>

    <div class="kartu overflow-hidden">
        @forelse ($this->faqs as $faq)
            <div wire:key="faq-{{ $faq->id }}" class="flex items-start gap-4 border-b border-primary-50 px-5 py-4 last:border-b-0">
                <span class="mt-0.5 w-8 shrink-0 text-sm font-semibold text-zinc-400">{{ $faq->urutan }}.</span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-primary-900">{{ $faq->pertanyaan }}</p>
                    <p class="line-clamp-2 text-sm whitespace-pre-line text-zinc-600">{{ $faq->jawaban }}</p>
                </div>
                <button type="button" wire:click="togglePublish({{ $faq->id }})" title="Klik untuk mengubah status" @class([
                    'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium transition',
                    'bg-primary-100 text-primary-700 hover:bg-primary-200' => $faq->is_published,
                    'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => ! $faq->is_published,
                ])>
                    <flux:icon :name="$faq->is_published ? 'eye' : 'eye-slash'" variant="micro" class="size-3.5" />
                    {{ $faq->is_published ? 'Terbit' : 'Draf' }}
                </button>
                <div class="flex shrink-0 gap-1">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="ubah({{ $faq->id }})" aria-label="Ubah" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600!" wire:click="hapus({{ $faq->id }})"
                        wire:confirm="Hapus FAQ ini?" aria-label="Hapus" />
                </div>
            </div>
        @empty
            <x-empty-state icon="question-mark-circle" title="Belum ada FAQ" />
        @endforelse
    </div>

    <flux:modal name="form-faq" class="w-full max-w-lg">
        <form wire:submit="simpan" class="space-y-5">
            <flux:heading size="lg">{{ $editId ? 'Ubah FAQ' : 'Tambah FAQ' }}</flux:heading>
            <flux:input wire:model="pertanyaan" label="Pertanyaan" required />
            <flux:textarea wire:model="jawaban" label="Jawaban" rows="5" required />
            <div class="grid items-end gap-4 sm:grid-cols-2">
                <flux:input type="number" min="0" max="999" wire:model="urutan" label="Urutan" required />
                <flux:switch wire:model="is_published" label="Tampilkan di publik" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
