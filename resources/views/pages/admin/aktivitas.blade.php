<?php

use App\Models\Artikel;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Log Aktivitas')] class extends Component {
    use WithPagination;

    public const LOG = [
        'pengajuan' => 'Pengajuan',
        'tanah_wakaf' => 'Tanah Wakaf',
        'pengguna' => 'Pengguna',
        'konten' => 'Konten',
    ];

    public const EVENT = [
        'created' => ['Dibuat', 'green'],
        'updated' => ['Diubah', 'blue'],
        'deleted' => ['Dihapus', 'red'],
    ];

    /** Label kolom yang lebih mudah dibaca. */
    public const LABEL_KOLOM = [
        'name' => 'Nama', 'username' => 'Username', 'email' => 'Email', 'phone' => 'No. HP', 'kecamatan_id' => 'Kecamatan (ID)',
        'is_active' => 'Aktif', 'is_published' => 'Dipublikasikan', 'judul' => 'Judul', 'ringkasan' => 'Ringkasan',
        'kode' => 'Kode', 'nama' => 'Nama', 'nazir_id' => 'Nazir (ID)', 'nama_nazir' => 'Nama Nazir', 'wakif_nama' => 'Nama Wakif',
        'wakif_nik' => 'NIK Wakif', 'desa_id' => 'Desa (ID)', 'alamat' => 'Alamat', 'luas_m2' => 'Luas (m²)',
        'jenis_penggunaan' => 'Jenis Penggunaan', 'status_sertifikat' => 'Status Sertifikat', 'nomor_aiw' => 'Nomor AIW',
        'tanggal_aiw' => 'Tanggal AIW', 'nomor_sertifikat' => 'Nomor Sertifikat', 'tanggal_sertifikat' => 'Tanggal Sertifikat',
        'latitude' => 'Latitude', 'longitude' => 'Longitude', 'batas_geojson' => 'Batas Bidang', 'foto' => 'Foto', 'sumber' => 'Sumber',
        'dari' => 'Dari', 'ke' => 'Ke', 'catatan' => 'Catatan',
    ];

    /** Kolom yang tidak ditampilkan nilainya (privasi / terlalu panjang). */
    public const DISAMARKAN = ['wakif_nik', 'batas_geojson'];

    #[Url(as: 'log', except: '')]
    public string $logName = '';

    #[Url(as: 'pelaku', except: '')]
    public string $cariPelaku = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('pengguna.kelola'), 403);
    }

    public function updated(string $properti): void
    {
        if (in_array($properti, ['logName', 'cariPelaku'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function aktivitas()
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when(array_key_exists($this->logName, self::LOG), fn (Builder $q) => $q->where('log_name', $this->logName))
            ->when(trim($this->cariPelaku) !== '', fn (Builder $q) => $q->whereHasMorph(
                'causer', [User::class],
                fn (Builder $c) => $c->where('name', 'like', '%'.trim($this->cariPelaku).'%')
            ))
            ->latest()->latest('id')
            ->paginate(20);
    }

    public function labelSubjek(Activity $a): string
    {
        $s = $a->subject;

        return match (true) {
            $s instanceof TanahWakaf => "Tanah {$s->kode} · {$s->nama}",
            $s instanceof Pengajuan => 'Pengajuan '.($s->nomor ?? '#'.$s->id),
            $s instanceof User => "Pengguna {$s->name}",
            $s instanceof Artikel => "Artikel {$s->judul}",
            $s !== null => class_basename($s).' #'.$s->getKey(),
            $a->subject_type !== null => class_basename($a->subject_type).' #'.$a->subject_id.' (sudah dihapus)',
            default => '—',
        };
    }

    /**
     * Daftar perubahan [kolom, lama, baru] dari attribute_changes (model) atau properties dari/ke (pengajuan).
     *
     * @return list<array{0: string, 1: string|null, 2: string|null}>
     */
    public function perubahan(Activity $a): array
    {
        $baru = (array) ($a->attribute_changes?->get('attributes') ?? []);
        $lama = (array) ($a->attribute_changes?->get('old') ?? []);
        $baris = [];

        foreach (array_unique([...array_keys($lama), ...array_keys($baru)]) as $kolom) {
            $baris[] = [
                self::LABEL_KOLOM[$kolom] ?? $kolom,
                array_key_exists($kolom, $lama) ? $this->nilai($kolom, $lama[$kolom]) : null,
                array_key_exists($kolom, $baru) ? $this->nilai($kolom, $baru[$kolom]) : null,
            ];
        }

        $props = $a->properties?->all() ?? [];
        if (isset($props['dari']) || isset($props['ke'])) {
            $baris[] = ['Status', $this->nilai('dari', $props['dari'] ?? null), $this->nilai('ke', $props['ke'] ?? null)];
        }
        if (filled($props['catatan'] ?? null)) {
            $baris[] = ['Catatan', null, $this->nilai('catatan', $props['catatan'])];
        }

        return $baris;
    }

    private function nilai(string $kolom, mixed $v): string
    {
        if (in_array($kolom, self::DISAMARKAN, true)) {
            return $v === null || $v === '' ? '—' : '(disembunyikan)';
        }

        if (is_string($v) || is_int($v)) {
            $enum = match ($kolom) {
                'dari', 'ke' => \App\Enums\StatusPengajuan::tryFrom((string) $v),
                'status_sertifikat' => \App\Enums\StatusSertifikat::tryFrom((string) $v),
                'jenis_penggunaan' => \App\Enums\JenisPenggunaan::tryFrom((string) $v),
                default => null,
            };
            if ($enum) {
                return $enum->label();
            }
        }

        return match (true) {
            $v === null || $v === '' => '—',
            is_bool($v) => $v ? 'Ya' : 'Tidak',
            is_array($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => str((string) $v)->limit(120)->toString(),
        };
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Log Aktivitas</flux:heading>
        <flux:text class="mt-1">Jejak audit: siapa mengubah apa dan kapan, beserta nilai lama → baru.</flux:text>
    </div>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-3">
        <flux:select wire:model.live="logName" aria-label="Filter jenis log">
            <flux:select.option value="">Semua jenis</flux:select.option>
            @foreach ($this::LOG as $nilai => $label)
                <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.400ms="cariPelaku" icon="magnifying-glass" placeholder="Cari nama pelaku" aria-label="Cari pelaku" />
        </div>
    </div>

    <div class="kartu overflow-hidden">
        @forelse ($this->aktivitas as $a)
            <article wire:key="aktivitas-{{ $a->id }}" class="space-y-2 border-b border-primary-50 px-5 py-4 last:border-b-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($a->log_name)
                        <flux:badge size="sm" color="zinc">{{ $this::LOG[$a->log_name] ?? $a->log_name }}</flux:badge>
                    @endif
                    @if ($ev = $this::EVENT[$a->event] ?? null)
                        <flux:badge size="sm" :color="$ev[1]">{{ $ev[0] }}</flux:badge>
                    @endif
                    <p class="font-medium text-primary-900">{{ $a->description }}</p>
                </div>
                <p class="text-xs text-zinc-500">
                    {{ $this->labelSubjek($a) }}
                    · oleh <span class="font-medium text-zinc-700">{{ $a->causer?->name ?? 'Sistem' }}</span>
                    · <time datetime="{{ $a->created_at?->toIso8601String() }}" title="{{ $a->created_at?->translatedFormat('d F Y H:i:s') }}">{{ $a->created_at?->translatedFormat('d M Y, H:i') }}</time>
                </p>

                @if ($baris = $this->perubahan($a))
                    <div class="overflow-x-auto rounded-lg border border-zinc-100">
                        <table class="min-w-full text-xs">
                            <thead class="bg-zinc-50 text-zinc-500">
                                <tr>
                                    <th class="px-3 py-1.5 text-start font-medium">Kolom</th>
                                    <th class="px-3 py-1.5 text-start font-medium">Lama</th>
                                    <th class="px-3 py-1.5 text-start font-medium">Baru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($baris as [$kolom, $lama, $baru])
                                    <tr>
                                        <td class="px-3 py-1.5 font-medium text-zinc-700">{{ $kolom }}</td>
                                        <td class="px-3 py-1.5 break-all text-red-700">{{ $lama ?? '' }}</td>
                                        <td class="px-3 py-1.5 break-all text-primary-700">{{ $baru ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        @empty
            <x-empty-state icon="clock" title="Belum ada aktivitas" />
        @endforelse
    </div>

    <flux:pagination :paginator="$this->aktivitas" />
</div>
