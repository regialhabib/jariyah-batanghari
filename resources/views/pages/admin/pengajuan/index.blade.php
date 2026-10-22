<?php

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengajuan')] class extends Component {
    use WithPagination;

    /** 'periksa' = perlu tindakan pengguna ini, '' = semua */
    #[Url]
    public string $tab = '';

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $jenis = '';

    #[Url]
    public string $kecamatan = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Pengajuan::class);
    }

    protected function pengguna(): User
    {
        return auth()->user();
    }

    public function updated(string $properti): void
    {
        $this->resetPage();
    }

    public function pilihTab(string $tab): void
    {
        $this->tab = $tab === 'periksa' ? 'periksa' : '';
        $this->status = '';
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status', 'jenis', 'kecamatan', 'tab');
        $this->resetPage();
    }

    /**
     * Batasi ke pengajuan yang menunggu tindakan pengguna (sesuai wewenang role).
     *
     * @param  Builder<Pengajuan>  $query
     */
    protected function hanyaPerluDiperiksa(Builder $query): void
    {
        $user = $this->pengguna();
        $sertifikat = [JenisPengajuan::SertifikatBersertifikat->value, JenisPengajuan::SertifikatBelum->value];

        if ($user->isBpn()) {
            $query->whereIn('jenis', $sertifikat)
                ->whereIn('status', [StatusPengajuan::Diajukan->value, StatusPengajuan::Terverifikasi->value]);
        } elseif ($user->isKua()) {
            $query->where('jenis', JenisPengajuan::Aiw->value)->where('status', StatusPengajuan::Diajukan->value);
        } else {
            $query->whereRaw('1 = 0');
        }
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

    #[Computed]
    public function jumlahPerluDiperiksa(): int
    {
        $query = Pengajuan::query()->forUser($this->pengguna());
        $this->hanyaPerluDiperiksa($query);

        return $query->count();
    }

    #[Computed]
    public function pengajuans(): LengthAwarePaginator
    {
        $q = trim(mb_substr($this->q, 0, 100));
        $status = StatusPengajuan::tryFrom($this->status);

        return Pengajuan::query()->forUser($this->pengguna())
            ->where('status', '!=', StatusPengajuan::Draf->value)
            ->with(['tanahWakaf.desa.kecamatan', 'nazir'])
            ->when($this->tab === 'periksa', fn (Builder $query) => $this->hanyaPerluDiperiksa($query))
            ->when($status && $status !== StatusPengajuan::Draf, fn (Builder $query) => $query->where('status', $status->value))
            ->when(JenisPengajuan::tryFrom($this->jenis), fn (Builder $query, JenisPengajuan $j) => $query->where('jenis', $j->value))
            ->when((int) $this->kecamatan, fn (Builder $query, int $id) => $query->whereHas('tanahWakaf.desa', fn (Builder $d) => $d->where('kecamatan_id', $id)))
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('nomor', 'like', "%{$q}%")
                ->orWhereHas('tanahWakaf', fn (Builder $t) => $t->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%"))
                ->orWhereHas('nazir', fn (Builder $n) => $n->where('name', 'like', "%{$q}%"))))
            ->orderByDesc('diajukan_at')
            ->orderByDesc('id')
            ->paginate(10);
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman judul="Pengajuan" :deskripsi="auth()->user()->isKua()
        ? 'Pengajuan di Kecamatan '.auth()->user()->kecamatan?->nama.'. Anda memeriksa pengajuan Akta Ikrar Wakaf (AIW).'
        : 'Pengajuan dari Nazir. BPN memeriksa pengajuan sertifikat wakaf.'" />

    <div class="flex flex-wrap gap-2" role="tablist">
        <button type="button" role="tab" wire:click="pilihTab('periksa')" aria-selected="{{ $tab === 'periksa' ? 'true' : 'false' }}"
            @class(['inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition',
                'bg-primary-700 text-white' => $tab === 'periksa', 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-primary-50' => $tab !== 'periksa'])>
            Perlu Diperiksa
            <span @class(['rounded-full px-2 text-xs', 'bg-white/20' => $tab === 'periksa', 'bg-primary-100 text-primary-800' => $tab !== 'periksa'])>{{ $this->jumlahPerluDiperiksa }}</span>
        </button>
        <button type="button" role="tab" wire:click="pilihTab('')" aria-selected="{{ $tab !== 'periksa' ? 'true' : 'false' }}"
            @class(['inline-flex items-center rounded-full px-4 py-1.5 text-sm font-medium transition',
                'bg-primary-700 text-white' => $tab !== 'periksa', 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-primary-50' => $tab === 'periksa'])>
            Semua
        </button>
    </div>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.400ms="q" icon="magnifying-glass" placeholder="Cari nomor, nama tanah, atau Nazir..." maxlength="100" aria-label="Cari" />
        </div>
        <flux:select wire:model.live="status" aria-label="Status">
            <flux:select.option value="">Semua Status</flux:select.option>
            @foreach (StatusPengajuan::cases() as $s)
                @continue($s === StatusPengajuan::Draf)
                <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="jenis" aria-label="Jenis pengajuan">
            <flux:select.option value="">Semua Jenis</flux:select.option>
            @foreach (JenisPengajuan::cases() as $j)
                <flux:select.option value="{{ $j->value }}">{{ $j->labelSingkat() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="kecamatan" aria-label="Kecamatan">
            <flux:select.option value="">Semua Kecamatan</flux:select.option>
            @foreach ($this->daftarKecamatan as $id => $nama)
                <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="kartu overflow-hidden p-5">
        <div class="mb-3 flex items-center justify-between text-sm text-zinc-600">
            <span>{{ number_format($this->pengajuans->total(), 0, ',', '.') }} pengajuan</span>
            <flux:button size="sm" variant="ghost" wire:click="resetFilter">Reset filter</flux:button>
        </div>

        @if ($this->pengajuans->isEmpty())
            <x-empty-state icon="clipboard-document-list" title="Tidak ada pengajuan" description="Belum ada pengajuan yang sesuai dengan filter." />
        @else
            <div class="overflow-x-auto">
                <flux:table :paginate="$this->pengajuans">
                    <flux:table.columns>
                        <flux:table.column>Nomor</flux:table.column>
                        <flux:table.column>Tanah Wakaf</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Nazir</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->pengajuans as $p)
                            <flux:table.row :key="$p->id">
                                <flux:table.cell class="font-mono text-xs">{{ $p->nomor ?? '-' }}</flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-medium text-zinc-800">{{ $p->tanahWakaf->nama }}</p>
                                    <p class="text-xs text-zinc-500">{{ $p->tanahWakaf->desa?->nama }}, {{ $p->tanahWakaf->desa?->kecamatan?->nama }}</p>
                                </flux:table.cell>
                                <flux:table.cell>{{ $p->jenis->labelSingkat() }}</flux:table.cell>
                                <flux:table.cell>{{ $p->nazir->name }}</flux:table.cell>
                                <flux:table.cell>{{ $p->diajukan_at?->locale('id')->translatedFormat('j M Y') ?? '-' }}</flux:table.cell>
                                <flux:table.cell><x-badge-status :status="$p->status" /></flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" :variant="$tab === 'periksa' ? 'primary' : 'ghost'" :href="route('admin.pengajuan.show', $p)" wire:navigate>
                                        {{ $tab === 'periksa' ? 'Periksa' : 'Detail' }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
</div>
