<?php

use App\Models\Pesan;
use Flux\Flux;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Hubungi Kami')] class extends Component {
    /** Batas kiriman per IP dalam satu jendela waktu (docs/06-keamanan.md). */
    public const BATAS_KIRIM = 3;

    public const JENDELA_DETIK = 600;

    public string $nama = '';

    public string $kontak = '';

    public string $pesan = '';

    /** Honeypot: tidak terlihat oleh manusia; jika terisi, kiriman dibuang diam-diam. */
    public string $website = '';

    public bool $terkirim = false;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'kontak' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, \Closure $fail) {
                $value = trim((string) $value);
                $email = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                $hp = preg_match('/^(\+62|62|0)[0-9][0-9\s\-]{7,14}$/', $value) === 1;

                if (! $email && ! $hp) {
                    $fail('Masukkan alamat email atau nomor HP yang valid.');
                }
            }],
            'pesan' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama lengkap',
            'kontak' => 'email / no. HP',
            'pesan' => 'pesan',
        ];
    }

    public function kirim(): void
    {
        $this->terkirim = false;

        // Bot mengisi semua field: pura-pura berhasil tanpa menyimpan apa pun.
        if (filled($this->website)) {
            $this->selesai();

            return;
        }

        $data = $this->validate();

        $kunci = 'kontak:'.request()->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::BATAS_KIRIM)) {
            $menit = max(1, (int) ceil(RateLimiter::availableIn($kunci) / 60));
            $this->addError('kirim', "Anda sudah mengirim terlalu banyak pesan. Silakan coba lagi dalam {$menit} menit.");

            return;
        }

        RateLimiter::hit($kunci, self::JENDELA_DETIK);

        Pesan::query()->create([
            'nama' => trim($data['nama']),
            'kontak' => trim($data['kontak']),
            'isi' => trim($data['pesan']),
            'ip' => request()->ip(),
        ]);

        $this->selesai();
    }

    private function selesai(): void
    {
        $this->reset('nama', 'kontak', 'pesan', 'website');
        $this->resetValidation();
        $this->terkirim = true;

        Flux::toast(variant: 'success', text: 'Pesan Anda berhasil dikirim. Terima kasih!');
    }
}; ?>

<div>
    <x-page-header title="Hubungi Kami" icon="chat-bubble-left-right" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Hubungi Kami']]" />

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-12 lg:px-8">
        {{-- Info kontak --}}
        <section class="kartu p-5 sm:p-6 lg:col-span-4" aria-labelledby="judul-info-kontak">
            <h2 id="judul-info-kontak" class="sr-only">Informasi Kontak</h2>
            <dl class="space-y-5">
                @foreach ([
                    ['icon' => 'map-pin', 'label' => 'Alamat', 'nilai' => setting('alamat')],
                    ['icon' => 'phone', 'label' => 'Telepon', 'nilai' => setting('telepon')],
                    ['icon' => 'envelope', 'label' => 'Email', 'nilai' => setting('email')],
                    ['icon' => 'clock', 'label' => 'Jam Operasional', 'nilai' => setting('jam_operasional')],
                ] as $info)
                    <div class="flex gap-4">
                        <x-ikon-kotak :icon="$info['icon']" size="sm" />
                        <div class="min-w-0">
                            <dt class="font-semibold text-primary-900">{{ $info['label'] }}</dt>
                            <dd class="mt-0.5 break-words text-sm text-zinc-600">
                                @if ($info['label'] === 'Telepon' && filled($info['nilai']))
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $info['nilai']) }}" class="hover:text-primary-700">{{ $info['nilai'] }}</a>
                                @elseif ($info['label'] === 'Email' && filled($info['nilai']))
                                    <a href="mailto:{{ $info['nilai'] }}" class="break-all hover:text-primary-700">{{ $info['nilai'] }}</a>
                                @else
                                    {{ $info['nilai'] ?: '-' }}
                                @endif
                            </dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Form --}}
        <section class="kartu p-5 sm:p-6 lg:col-span-5" aria-labelledby="judul-form-kontak">
            <h2 id="judul-form-kontak" class="text-lg font-semibold text-primary-900">Kirim Pesan</h2>
            <p class="mt-1 text-sm text-zinc-600">Untuk pertanyaan, saran, atau kendala, silakan isi form berikut.</p>

            @if ($terkirim)
                <flux:callout variant="success" icon="check-circle" class="mt-4" heading="Pesan Anda berhasil dikirim. Terima kasih!" />
            @endif

            @error('kirim')
                <flux:callout variant="danger" icon="exclamation-triangle" class="mt-4" :heading="$message" />
            @enderror

            <form wire:submit="kirim" class="mt-5 space-y-4" novalidate>
                <flux:input wire:model="nama" label="Nama Lengkap" placeholder="Masukkan nama lengkap" autocomplete="name" required />
                <flux:input wire:model="kontak" label="Email / No. HP" placeholder="Masukkan email atau nomor HP" autocomplete="email" required />
                <flux:textarea wire:model="pesan" label="Pesan" placeholder="Tulis pesan Anda..." rows="5" required />

                {{-- Honeypot (disembunyikan dari pengguna dan pembaca layar) --}}
                <div class="absolute -start-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                    <label for="kontak-website">Website</label>
                    <input type="text" id="kontak-website" wire:model="website" tabindex="-1" autocomplete="off">
                </div>

                <flux:button type="submit" variant="primary" class="w-full" icon="paper-airplane">
                    <span wire:loading.remove wire:target="kirim">Kirim Pesan</span>
                    <span wire:loading wire:target="kirim">Mengirim...</span>
                </flux:button>
            </form>
        </section>

        {{-- Kontak Nazir --}}
        <aside class="kartu relative overflow-hidden p-5 sm:p-6 lg:col-span-3">
            <x-ornamen-daun class="pointer-events-none absolute -bottom-6 -end-4 size-32 text-primary-100" />
            <div class="relative">
                <x-ikon-kotak icon="user-group" variant="soft" />
                <h2 class="mt-4 text-lg font-semibold text-primary-900">Kontak Nazir</h2>
                <p class="mt-2 text-sm text-zinc-600">Butuh bantuan terkait pengajuan wakaf? Hubungi Nazir yang terdaftar di wilayah Anda.</p>
                <a href="{{ route('nazir.publik') }}" wire:navigate class="btn-outline mt-5 w-full">Lihat Daftar Nazir</a>
            </div>
        </aside>
    </div>
</div>
