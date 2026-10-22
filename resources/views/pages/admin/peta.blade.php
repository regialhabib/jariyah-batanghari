<?php

use App\Enums\StatusSertifikat;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Peta Wakaf')] class extends Component {
    #[Url]
    public string $kecamatan = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TanahWakaf::class);
    }

    protected function pengguna(): User
    {
        return auth()->user();
    }

    public function resetFilter(): void
    {
        $this->reset('kecamatan', 'status');
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarKecamatan(): array
    {
        $user = $this->pengguna();

        return Kecamatan::query()->orderBy('nama')
            ->when($user->isKua(), fn ($q) => $q->whereKey($user->kecamatan_id))
            ->pluck('nama', 'id')->all();
    }

    /**
     * Semua tanah dalam cakupan pengguna, termasuk yang belum dipublikasikan.
     *
     * @return Collection<int, TanahWakaf>
     */
    #[Computed]
    public function tanahs(): Collection
    {
        return TanahWakaf::query()->forUser($this->pengguna())
            ->with(['desa.kecamatan', 'nazir'])
            ->when((int) $this->kecamatan, fn (Builder $q, int $id) => $q->whereHas('desa', fn (Builder $d) => $d->where('kecamatan_id', $id)))
            ->when(StatusSertifikat::tryFrom($this->status), fn (Builder $q, StatusSertifikat $s) => $q->where('status_sertifikat', $s->value))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function dataPeta(): array
    {
        // Halaman publik 404 untuk tanah belum terbit: arahkan ke form ubah (BPN) atau tanpa tombol (KUA).
        $bisaUbah = $this->pengguna()->can('create', TanahWakaf::class);

        return $this->tanahs
            ->map(fn (TanahWakaf $t) => [...$t->dataPublik(), 'url' => $bisaUbah ? route('admin.tanah.edit', $t) : null])
            ->all();
    }

    /**
     * @return array{total: int, sudah: int, proses: int, belum: int, belum_terbit: int}
     */
    #[Computed]
    public function jumlah(): array
    {
        $t = $this->tanahs;

        return [
            'total' => $t->count(),
            'sudah' => $t->where('status_sertifikat', StatusSertifikat::Sudah)->count(),
            'proses' => $t->where('status_sertifikat', StatusSertifikat::Proses)->count(),
            'belum' => $t->where('status_sertifikat', StatusSertifikat::Belum)->count(),
            'belum_terbit' => $t->where('is_published', false)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Peta Wakaf</flux:heading>
        <flux:text class="mt-1">Seluruh tanah wakaf dalam wilayah Anda, termasuk yang belum dipublikasikan.</flux:text>
    </div>

    <div class="kartu flex flex-wrap items-end gap-3 p-5">
        <div class="w-full sm:w-56">
            <flux:select wire:model.live="kecamatan" label="Kecamatan">
                <flux:select.option value="">Semua Kecamatan</flux:select.option>
                @foreach ($this->daftarKecamatan as $id => $nama)
                    <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-56">
            <flux:select wire:model.live="status" label="Status Sertifikat">
                <flux:select.option value="">Semua Status</flux:select.option>
                @foreach (StatusSertifikat::cases() as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button variant="ghost" wire:click="resetFilter">Reset</flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Total', $this->jumlah['total'], 'text-primary-900'],
            ['Sudah Bersertifikat', $this->jumlah['sudah'], 'text-primary-700'],
            ['Dalam Proses', $this->jumlah['proses'], 'text-blue-700'],
            ['Belum Bersertifikat', $this->jumlah['belum'], 'text-amber-700'],
            ['Belum Dipublikasikan', $this->jumlah['belum_terbit'], 'text-zinc-700'],
        ] as [$label, $nilai, $warna])
            <div class="kartu p-4">
                <p class="text-xs text-zinc-500">{{ $label }}</p>
                <p class="text-xl font-bold {{ $warna }}">{{ number_format($nilai, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <x-peta mode="data" :data="$this->dataPeta" wire:key="peta-{{ md5($kecamatan.'|'.$status.'|'.$this->tanahs->pluck('id')->implode(',')) }}"
        tinggi="h-[480px] lg:h-[620px]" />
</div>
