<?php

use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\Kecamatan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Form Pengguna')] class extends Component {
    use ProfileValidationRules;

    /** Role yang boleh dibuat lewat form ini (Nazir hanya lewat registrasi publik). */
    public const ROLE_PETUGAS = ['admin_bpn', 'bpn', 'kua'];

    #[Locked]
    public ?int $userId = null;

    /** Mode Nazir: hanya nama, email, No. HP, dan status aktif yang bisa diubah. */
    #[Locked]
    public bool $modeNazir = false;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'bpn';

    public string $kecamatan_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public function mount(?User $user = null): void
    {
        $user = $user?->exists ? $user : null;
        $user ? $this->authorize('update', $user) : $this->authorize('create', User::class);

        if ($user) {
            $this->userId = $user->id;
            $this->modeNazir = $user->isNazir();
            $this->name = $user->name;
            $this->username = (string) $user->username;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
            $this->role = $user->peran()?->value ?? 'bpn';
            $this->kecamatan_id = (string) ($user->kecamatan_id ?? '');
            $this->is_active = $user->is_active;
        }
    }

    #[Computed]
    public function pengguna(): ?User
    {
        return $this->userId ? User::query()->findOrFail($this->userId) : null;
    }

    #[Computed]
    public function kecamatans()
    {
        return Kecamatan::query()->orderBy('nama')->get(['id', 'nama']);
    }

    public function akunSendiri(): bool
    {
        return $this->userId !== null && $this->userId === auth()->id();
    }

    protected function rules(): array
    {
        $rules = [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->userId),
            'phone' => $this->phoneRules(required: false),
            'is_active' => ['boolean'],
        ];

        if ($this->modeNazir) {
            return $rules;
        }

        return $rules + [
            // Huruf kecil, angka, titik, strip, garis bawah (tanpa "@" agar tidak tertukar dengan email saat login).
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique(User::class, 'username')->ignore($this->userId)],
            'role' => ['required', Rule::in(self::ROLE_PETUGAS)],
            'kecamatan_id' => ['nullable', 'required_if:role,kua', 'exclude_unless:role,kua', 'integer', Rule::exists('kecamatans', 'id')],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'No. HP',
            'kecamatan_id' => 'kecamatan',
            'is_active' => 'status aktif',
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->profileMessages(),
            'kecamatan_id.required_if' => 'Petugas KUA wajib terikat pada satu kecamatan.',
            'role.in' => 'Role tidak valid. Akun Nazir hanya dibuat lewat registrasi publik.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, strip, dan garis bawah.',
        ];
    }

    public function simpan(): void
    {
        $user = $this->pengguna;
        $user ? $this->authorize('update', $user) : $this->authorize('create', User::class);

        // Data role di akun yang sudah ada selalu dibaca ulang dari database, bukan dari klien.
        if ($user) {
            $this->modeNazir = $user->isNazir();
            if ($this->akunSendiri()) {
                $this->role = $user->peran()?->value ?? $this->role; // admin tidak bisa mengubah role sendiri
                $this->is_active = true;                            // dan tidak bisa menonaktifkan diri
            }
        }

        // Login membandingkan username tanpa membedakan huruf besar/kecil.
        $this->username = strtolower(trim($this->username));

        $data = $this->validate();

        if ($user && $user->is_active && ! $data['is_active']) {
            $this->authorize('toggleAktif', $user);
        }

        DB::transaction(function () use ($user, $data) {
            $user ??= new User;

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
                'is_active' => $data['is_active'],
            ]);

            if (! $this->modeNazir) {
                $roleLama = $user->exists ? $user->peran()?->value : null;

                $user->username = $data['username'];
                $user->kecamatan_id = $data['role'] === Role::Kua->value ? (int) $data['kecamatan_id'] : null;
                if (filled($data['password'] ?? null)) {
                    $user->password = $data['password'];
                }
                // Akun petugas dibuat oleh admin: email dianggap terverifikasi.
                $user->email_verified_at ??= now();
                $user->save();
                $user->syncRoles([$data['role']]);

                if ($roleLama !== $data['role']) {
                    activity('pengguna')->performedOn($user)
                        ->withProperties(['dari' => $roleLama, 'ke' => $data['role']])
                        ->log($roleLama ? 'Role pengguna diubah' : 'Petugas dibuat dengan role '.Role::from($data['role'])->label());
                }
            } else {
                // Email Nazir yang diganti admin harus diverifikasi ulang oleh pemiliknya.
                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }
                $user->save();
            }
        });

        Flux::toast(variant: 'success', text: $this->userId ? 'Data pengguna diperbarui.' : 'Akun petugas dibuat.');
        $this->redirectRoute('admin.pengguna.index', navigate: true);
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-primary-700 hover:underline">
            <flux:icon.arrow-left variant="micro" class="size-4" /> Kembali ke daftar pengguna
        </a>
        <flux:heading size="xl" level="1" class="mt-2">
            {{ $userId ? 'Ubah Pengguna' : 'Tambah Petugas' }}
        </flux:heading>
        @if ($modeNazir)
            <flux:text class="mt-1">Akun Nazir: hanya nama, email, No. HP, dan status aktif yang dapat diubah. Role Nazir tidak bisa diganti menjadi petugas.</flux:text>
        @elseif (! $userId)
            <flux:text class="mt-1">Akun petugas langsung aktif dan terverifikasi. Akun Nazir dibuat sendiri oleh Nazir lewat registrasi.</flux:text>
        @endif
    </div>

    <form wire:submit="simpan" class="kartu space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="name" label="Nama Lengkap" required />
            @unless ($modeNazir)
                <flux:input wire:model="username" label="Username" required autocomplete="off" />
            @endunless
            <flux:input wire:model="email" type="email" label="Email" required />
            <flux:input wire:model="phone" label="No. HP (opsional)" placeholder="08xxxxxxxxxx" />
        </div>

        @unless ($modeNazir)
            <flux:separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="role" label="Role" :disabled="$this->akunSendiri()" required>
                    @foreach ($this::ROLE_PETUGAS as $r)
                        <flux:select.option value="{{ $r }}">{{ \App\Enums\Role::from($r)->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($role === 'kua')
                    <flux:select wire:model="kecamatan_id" label="Kecamatan" required>
                        <flux:select.option value="">Pilih kecamatan</flux:select.option>
                        @foreach ($this->kecamatans as $k)
                            <flux:select.option value="{{ $k->id }}">{{ $k->nama }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            <flux:separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="password" type="password" viewable autocomplete="new-password"
                    :label="$userId ? 'Password Baru (opsional)' : 'Password'" :required="! $userId" />
                <flux:input wire:model="password_confirmation" type="password" viewable autocomplete="new-password"
                    label="Konfirmasi Password" :required="! $userId" />
            </div>
            @if ($userId)
                <flux:text class="text-xs">Kosongkan bila password tidak diubah. Anda juga bisa mengirim tautan reset password dari daftar pengguna.</flux:text>
            @endif
        @endunless

        @unless ($this->akunSendiri())
            <flux:switch wire:model="is_active" label="Akun aktif (bisa login)" />
        @endunless

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('admin.pengguna.index')" wire:navigate>Batal</flux:button>
            <flux:button type="submit" variant="primary">Simpan</flux:button>
        </div>
    </form>
</div>
