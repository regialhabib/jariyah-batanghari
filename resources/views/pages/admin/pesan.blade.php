<?php

use App\Models\Pesan;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pesan Masuk')] class extends Component {
    use WithPagination;

    #[Url(as: 'belum', except: false)]
    public bool $belumDibaca = false;

    public ?int $dibukaId = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('pesan.lihat'), 403);
    }

    public function updatedBelumDibaca(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pesans()
    {
        return Pesan::query()
            ->when($this->belumDibaca, fn ($q) => $q->whereNull('dibaca_at'))
            ->latest()->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function jumlahBelumDibaca(): int
    {
        return Pesan::query()->whereNull('dibaca_at')->count();
    }

    #[Computed]
    public function dibuka(): ?Pesan
    {
        return $this->dibukaId ? Pesan::query()->find($this->dibukaId) : null;
    }

    public function buka(int $id): void
    {
        $pesan = Pesan::query()->findOrFail($id);
        if (! $pesan->dibaca_at) {
            $pesan->dibaca_at = now();
            $pesan->save();
        }

        $this->dibukaId = $pesan->id;
        unset($this->dibuka, $this->jumlahBelumDibaca);
        Flux::modal('baca-pesan')->show();
    }

    public function tandaiBelumDibaca(int $id): void
    {
        $pesan = Pesan::query()->findOrFail($id);
        $pesan->dibaca_at = null;
        $pesan->save();

        Flux::modal('baca-pesan')->close();
        $this->dibukaId = null;
    }

    public function hapus(int $id): void
    {
        Pesan::query()->findOrFail($id)->delete();

        Flux::modal('baca-pesan')->close();
        $this->dibukaId = null;
        Flux::toast(text: 'Pesan dihapus.');
    }

    /**
     * Tautan balas: mailto untuk email, wa.me untuk nomor HP.
     */
    public function tautanBalas(Pesan $pesan): ?string
    {
        $kontak = trim($pesan->kontak);
        if (filter_var($kontak, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:'.$kontak.'?subject='.rawurlencode('Balasan pesan Jariyah Batang Hari');
        }

        $angka = preg_replace('/\D/', '', $kontak);
        if ($angka !== '' && strlen($angka) >= 9) {
            return 'https://wa.me/'.(str_starts_with($angka, '0') ? '62'.substr($angka, 1) : $angka);
        }

        return null;
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman judul="Pesan Masuk" :deskripsi="'Pesan dari form Kontak di situs publik. '.$this->jumlahBelumDibaca.' belum dibaca.'">
        <flux:switch wire:model.live="belumDibaca" label="Hanya yang belum dibaca" />
    </x-admin.judul-halaman>

    <div class="kartu overflow-hidden">
        @forelse ($this->pesans as $pesan)
            <button type="button" wire:key="pesan-{{ $pesan->id }}" wire:click="buka({{ $pesan->id }})" @class([
                'flex w-full items-start gap-3 border-b border-primary-50 px-5 py-4 text-start transition last:border-b-0 hover:bg-primary-50/60',
                'bg-primary-50/50' => ! $pesan->dibaca_at,
            ])>
                <span @class(['mt-2 size-2 shrink-0 rounded-full', 'bg-primary-600' => ! $pesan->dibaca_at, 'bg-transparent' => $pesan->dibaca_at])>
                    <span class="sr-only">{{ $pesan->dibaca_at ? 'Sudah dibaca' : 'Belum dibaca' }}</span>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                        <p @class(['text-primary-900', 'font-semibold' => ! $pesan->dibaca_at, 'font-medium' => $pesan->dibaca_at])>
                            {{ $pesan->nama }} <span class="text-xs font-normal text-zinc-500">· {{ $pesan->kontak }}</span>
                        </p>
                        <time class="text-xs text-zinc-500" datetime="{{ $pesan->created_at->toIso8601String() }}">{{ $pesan->created_at->translatedFormat('d M Y, H:i') }}</time>
                    </div>
                    <p class="line-clamp-1 text-sm text-zinc-600">{{ $pesan->isi }}</p>
                </div>
            </button>
        @empty
            <x-empty-state icon="inbox" :title="$belumDibaca ? 'Tidak ada pesan belum dibaca' : 'Belum ada pesan'" />
        @endforelse
    </div>

    <flux:pagination :paginator="$this->pesans" />

    <flux:modal name="baca-pesan" class="w-full max-w-lg">
        @if ($pesan = $this->dibuka)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">Pesan dari {{ $pesan->nama }}</flux:heading>
                    <flux:text class="mt-1 text-xs">{{ $pesan->kontak }} · {{ $pesan->created_at->translatedFormat('l, d F Y H:i') }}</flux:text>
                </div>
                <div class="rounded-lg bg-zinc-50 p-4 text-sm whitespace-pre-line text-zinc-800">{{ $pesan->isi }}</div>
                <div class="flex flex-wrap justify-between gap-2">
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" icon="trash" class="hover:bg-red-50! hover:text-red-600!" wire:click="hapus({{ $pesan->id }})" wire:confirm="Hapus pesan ini?">Hapus</flux:button>
                        <flux:button size="sm" variant="ghost" icon="envelope" wire:click="tandaiBelumDibaca({{ $pesan->id }})">Tandai belum dibaca</flux:button>
                    </div>
                    @if ($balas = $this->tautanBalas($pesan))
                        <flux:button size="sm" variant="primary" icon="arrow-uturn-left" :href="$balas" target="_blank">Balas</flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
