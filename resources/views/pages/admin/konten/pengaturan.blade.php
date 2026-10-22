<?php

use App\Models\Pengaturan;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Pengaturan')] class extends Component {
    /**
     * Semua kunci pengaturan per grup: kunci => [label, jenis input, aturan validasi].
     * Jenis 'list' disimpan sebagai JSON list (dibaca lewat setting_list()).
     */
    public const SKEMA = [
        'kontak' => [
            'judul' => 'Kontak',
            'kunci' => [
                'alamat' => ['Alamat Kantor', 'textarea', ['nullable', 'string', 'max:500']],
                'telepon' => ['Telepon', 'text', ['nullable', 'string', 'max:50']],
                'email' => ['Email', 'email', ['nullable', 'email', 'max:150']],
                'jam_operasional' => ['Jam Operasional', 'text', ['nullable', 'string', 'max:150']],
            ],
        ],
        'beranda' => [
            'judul' => 'Beranda (Hero)',
            'kunci' => [
                'hero_judul' => ['Judul Hero', 'text', ['nullable', 'string', 'max:150']],
                'hero_subjudul' => ['Subjudul Hero', 'text', ['nullable', 'string', 'max:255']],
                'hero_deskripsi' => ['Deskripsi Hero', 'textarea', ['nullable', 'string', 'max:1000']],
            ],
        ],
        'tentang' => [
            'judul' => 'Tentang Kami',
            'kunci' => [
                'tentang_deskripsi' => ['Deskripsi', 'textarea', ['nullable', 'string', 'max:2000']],
                'tentang_tujuan' => ['Tujuan', 'textarea', ['nullable', 'string', 'max:1000']],
                'tentang_manfaat' => ['Manfaat (satu per baris)', 'list', ['nullable', 'string', 'max:3000']],
                'tentang_instansi' => ['Instansi Terkait (satu per baris)', 'list', ['nullable', 'string', 'max:3000']],
            ],
        ],
        'sosial' => [
            'judul' => 'Media Sosial',
            'kunci' => [
                'sosial_facebook' => ['URL Facebook', 'url', ['nullable', 'url:http,https', 'max:255']],
                'sosial_instagram' => ['URL Instagram', 'url', ['nullable', 'url:http,https', 'max:255']],
                'sosial_youtube' => ['URL YouTube', 'url', ['nullable', 'url:http,https', 'max:255']],
            ],
        ],
    ];

    /** @var array<string, string> */
    public array $nilai = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('konten.kelola'), 403);
    }

    public function mount(): void
    {
        $semua = Pengaturan::semua();

        foreach (self::SKEMA as $grup) {
            foreach ($grup['kunci'] as $kunci => [, $jenis]) {
                $this->nilai[$kunci] = $jenis === 'list'
                    ? implode("\n", setting_list($kunci))
                    : (string) ($semua[$kunci] ?? '');
            }
        }
    }

    protected function rules(): array
    {
        $rules = [];
        foreach (self::SKEMA as $grup) {
            foreach ($grup['kunci'] as $kunci => [, , $aturan]) {
                $rules["nilai.$kunci"] = $aturan;
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attr = [];
        foreach (self::SKEMA as $grup) {
            foreach ($grup['kunci'] as $kunci => [$label]) {
                $attr["nilai.$kunci"] = strtolower(preg_replace('/\s*\(.*\)$/', '', $label));
            }
        }

        return $attr;
    }

    public function simpan(): void
    {
        $this->validate();

        foreach (self::SKEMA as $namaGrup => $grup) {
            foreach ($grup['kunci'] as $kunci => [, $jenis]) {
                $isi = trim((string) ($this->nilai[$kunci] ?? ''));

                if ($jenis === 'list') {
                    $baris = collect(preg_split('/\R/', $isi))->map(fn ($b) => trim($b))->filter()->values()->all();
                    $isi = json_encode($baris, JSON_UNESCAPED_UNICODE);
                }

                Pengaturan::set($kunci, $isi, $namaGrup);
            }
        }

        Flux::toast(variant: 'success', text: 'Pengaturan disimpan.');
    }
}; ?>

<div class="space-y-6">
    <x-admin.konten.nav judul="Pengaturan Situs" aktif="admin.konten.pengaturan"
        deskripsi="Teks kontak, beranda, tentang kami, dan tautan media sosial di halaman publik." />

    <form wire:submit="simpan" class="space-y-6">
        @foreach ($this::SKEMA as $namaGrup => $grup)
            <section class="kartu space-y-4 p-5" wire:key="grup-{{ $namaGrup }}">
                <flux:heading size="lg">{{ $grup['judul'] }}</flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($grup['kunci'] as $kunci => [$label, $jenis])
                        <div @class(['md:col-span-2' => in_array($jenis, ['textarea', 'list'], true)]) wire:key="kunci-{{ $kunci }}">
                            @if ($jenis === 'textarea')
                                <flux:textarea wire:model="nilai.{{ $kunci }}" :label="$label" rows="3" />
                            @elseif ($jenis === 'list')
                                <flux:textarea wire:model="nilai.{{ $kunci }}" :label="$label" rows="4"
                                    description:trailing="Tulis satu poin per baris; baris kosong diabaikan." />
                            @else
                                <flux:input wire:model="nilai.{{ $kunci }}" :label="$label" :type="$jenis"
                                    :placeholder="$jenis === 'url' ? 'https://' : null" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="check">Simpan Pengaturan</flux:button>
        </div>
    </form>
</div>
