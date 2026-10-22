<?php

use App\Models\Artikel;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Artikel')] class extends Component {
    /** Ikon heroicon yang boleh dipilih untuk kartu artikel. */
    public const IKON = [
        'question-mark-circle', 'information-circle', 'book-open', 'document-text', 'user', 'user-group',
        'home-modern', 'building-library', 'shield-check', 'scale', 'map', 'clipboard-document-check',
        'light-bulb', 'heart', 'academic-cap', 'banknotes', 'newspaper', 'globe-asia-australia',
    ];

    public ?int $editId = null;

    public string $judul = '';

    public string $slug = '';

    public string $ikon = 'book-open';

    public string $ringkasan = '';

    public string $isi = '';

    public int $urutan = 1;

    public bool $is_published = true;

    /** Slug diubah manual: berhenti mengikuti judul. */
    public bool $slugManual = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    #[Computed]
    public function artikels()
    {
        return Artikel::query()->orderBy('urutan')->orderBy('id')->get();
    }

    protected function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash:ascii', Rule::unique('artikels', 'slug')->ignore($this->editId)],
            'ikon' => ['required', Rule::in(self::IKON)],
            'ringkasan' => ['required', 'string', 'max:500'],
            'isi' => ['required', 'string', 'max:65000'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['isi' => 'isi artikel', 'is_published' => 'status terbit'];
    }

    public function updatedJudul(): void
    {
        if (! $this->slugManual) {
            $this->slug = Str::slug($this->judul);
        }
    }

    public function updatedSlug(): void
    {
        $this->slug = Str::slug($this->slug);
        $this->slugManual = filled($this->slug);
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->urutan = (int) Artikel::query()->max('urutan') + 1;
        $this->dispatch('artikel-dimuat', isi: '');
        Flux::modal('form-artikel')->show();
    }

    public function ubah(int $id): void
    {
        $artikel = Artikel::query()->findOrFail($id);

        $this->resetForm();
        $this->editId = $artikel->id;
        $this->judul = $artikel->judul;
        $this->slug = $artikel->slug;
        $this->ikon = $artikel->ikon;
        $this->ringkasan = $artikel->ringkasan;
        $this->isi = $artikel->isi;
        $this->urutan = $artikel->urutan;
        $this->is_published = $artikel->is_published;
        $this->slugManual = true;

        $this->dispatch('artikel-dimuat', isi: $this->isi);
        Flux::modal('form-artikel')->show();
    }

    public function simpan(): void
    {
        $this->slug = Str::slug($this->slug ?: $this->judul);
        // Isi Trix kosong ("<div><br></div>") dianggap belum diisi.
        if (trim(strip_tags($this->isi)) === '') {
            $this->isi = '';
        }

        $data = $this->validate();

        $artikel = $this->editId ? Artikel::query()->findOrFail($this->editId) : new Artikel;
        $artikel->fill($data)->save(); // setIsiAttribute membersihkan HTML.

        Flux::modal('form-artikel')->close();
        Flux::toast(variant: 'success', text: $this->editId ? 'Artikel diperbarui.' : 'Artikel ditambahkan.');
        $this->resetForm();
        unset($this->artikels);
    }

    public function togglePublish(int $id): void
    {
        $artikel = Artikel::query()->findOrFail($id);
        $artikel->update(['is_published' => ! $artikel->is_published]);
        unset($this->artikels);

        Flux::toast(text: $artikel->is_published ? 'Artikel diterbitkan.' : 'Artikel disembunyikan dari publik.');
    }

    public function ubahUrutan(int $id, mixed $urutan): void
    {
        $nilai = filter_var($urutan, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
        if ($nilai === false) {
            Flux::toast(variant: 'danger', text: 'Urutan harus angka 0–999.');

            return;
        }

        Artikel::query()->findOrFail($id)->update(['urutan' => $nilai]);
        unset($this->artikels);
    }

    public function hapus(int $id): void
    {
        Artikel::query()->findOrFail($id)->delete();
        unset($this->artikels);

        Flux::toast(text: 'Artikel dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'judul', 'slug', 'ikon', 'ringkasan', 'isi', 'urutan', 'is_published', 'slugManual']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Artikel Informasi Wakaf" aktif="admin.konten.artikel"
        deskripsi="Artikel tampil di halaman Informasi Wakaf, berurutan sesuai kolom urutan.">
        <flux:button variant="primary" icon="plus" wire:click="tambah">Tambah Artikel</flux:button>
    </x-admin.konten.nav>

    <div class="kartu overflow-hidden">
        @if ($this->artikels->isEmpty())
            <x-empty-state icon="newspaper" title="Belum ada artikel" description="Tambahkan artikel pertama untuk halaman Informasi Wakaf." />
        @else
            <flux:table class="[&_td]:px-3 [&_th]:px-3">
                <flux:table.columns>
                    <flux:table.column class="w-24">Urutan</flux:table.column>
                    <flux:table.column>Artikel</flux:table.column>
                    <flux:table.column class="w-32">Status</flux:table.column>
                    <flux:table.column class="w-28" align="end">Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->artikels as $artikel)
                        <flux:table.row :key="'artikel-'.$artikel->id">
                            <flux:table.cell>
                                <input type="number" min="0" max="999" value="{{ $artikel->urutan }}" aria-label="Urutan {{ $artikel->judul }}"
                                    wire:change="ubahUrutan({{ $artikel->id }}, $event.target.value)"
                                    class="w-16 rounded-md border border-zinc-300 px-2 py-1 text-sm focus:border-primary-600 focus:outline-none" />
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-normal">
                                <div class="flex items-center gap-3">
                                    <x-ikon-kotak :icon="$artikel->ikon" size="sm" variant="soft" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-primary-900">{{ $artikel->judul }}</p>
                                        <p class="line-clamp-1 text-xs text-zinc-500">/informasi-wakaf/{{ $artikel->slug }}</p>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <button type="button" wire:click="togglePublish({{ $artikel->id }})" title="Klik untuk mengubah status"
                                    @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium transition',
                                        'bg-primary-100 text-primary-700 hover:bg-primary-200' => $artikel->is_published,
                                        'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => ! $artikel->is_published,
                                    ])>
                                    <flux:icon :name="$artikel->is_published ? 'eye' : 'eye-slash'" variant="micro" class="size-3.5" />
                                    {{ $artikel->is_published ? 'Terbit' : 'Draf' }}
                                </button>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" tooltip="Ubah" wire:click="ubah({{ $artikel->id }})" aria-label="Ubah" />
                                    <flux:button size="sm" variant="ghost" icon="trash" tooltip="Hapus" class="text-zinc-400! hover:bg-red-50! hover:text-red-600!"
                                        wire:click="hapus({{ $artikel->id }})" wire:confirm="Hapus artikel &quot;{{ $artikel->judul }}&quot;?" aria-label="Hapus" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="form-artikel" class="w-full max-w-3xl">
        <form wire:submit="simpan" class="space-y-5">
            <flux:heading size="lg">{{ $editId ? 'Ubah Artikel' : 'Tambah Artikel' }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.live.blur="judul" label="Judul" required />
                <flux:input wire:model.live.blur="slug" label="Slug (alamat URL)" description:trailing="Otomatis dari judul, boleh diubah." required />
            </div>

            <flux:field>
                <flux:label>Ikon</flux:label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this::IKON as $nama)
                        <label wire:key="ikon-{{ $nama }}" title="{{ $nama }}" @class([
                            'flex size-10 cursor-pointer items-center justify-center rounded-lg border transition',
                            'border-primary-700 bg-primary-700 text-white' => $ikon === $nama,
                            'border-zinc-200 text-primary-700 hover:bg-primary-50' => $ikon !== $nama,
                        ])>
                            <input type="radio" wire:model.live="ikon" value="{{ $nama }}" class="sr-only" />
                            <flux:icon :name="$nama" class="size-5" />
                            <span class="sr-only">{{ $nama }}</span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="ikon" />
            </flux:field>

            <flux:textarea wire:model="ringkasan" label="Ringkasan" rows="2" description:trailing="Tampil di kartu daftar artikel." required />

            <flux:field>
                <flux:label>Isi Artikel</flux:label>
                <div wire:ignore x-data
                    x-on:artikel-dimuat.window="$nextTick(() => $refs.trix.editor?.loadHTML($event.detail.isi ?? ''))">
                    <input id="isi-artikel" type="hidden" value="{{ $isi }}" />
                    <trix-editor x-ref="trix" input="isi-artikel"
                        x-on:trix-change="$wire.set('isi', $event.target.value, false)"
                        class="trix-content konten-artikel min-h-56 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm"></trix-editor>
                </div>
                <flux:error name="isi" />
            </flux:field>

            <div class="grid items-end gap-4 sm:grid-cols-2">
                <flux:input type="number" min="0" max="999" wire:model="urutan" label="Urutan" required />
                <flux:switch wire:model="is_published" label="Terbitkan di halaman publik" />
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
