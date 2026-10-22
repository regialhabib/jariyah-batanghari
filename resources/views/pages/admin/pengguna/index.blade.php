<?php

use App\Enums\Role;
use App\Models\Kecamatan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengguna')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $kecamatan = '';

    /** '' = semua, '1' = aktif, '0' = nonaktif. */
    #[Url(except: '')]
    public string $aktif = '';

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $properti): void
    {
        if (in_array($properti, ['cari', 'role', 'kecamatan', 'aktif'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function kecamatans()
    {
        return Kecamatan::query()->orderBy('nama')->get(['id', 'nama']);
    }

    #[Computed]
    public function pengguna()
    {
        $role = Role::tryFrom($this->role);

        return User::query()
            ->with('kecamatan')
            ->when(trim($this->cari) !== '', function (Builder $q) {
                $kata = '%'.trim($this->cari).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $kata)->orWhere('email', 'like', $kata)->orWhere('username', 'like', $kata));
            })
            ->when($role, fn (Builder $q) => $q->role($role->value))
            ->when(ctype_digit($this->kecamatan), fn (Builder $q) => $q->where('kecamatan_id', (int) $this->kecamatan))
            ->when(in_array($this->aktif, ['0', '1'], true), fn (Builder $q) => $q->where('is_active', $this->aktif === '1'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function toggleAktif(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('toggleAktif', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        Flux::toast(variant: 'success', text: $user->is_active ? "Akun {$user->name} diaktifkan." : "Akun {$user->name} dinonaktifkan.");
    }

    public function kirimResetPassword(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);

        $status = Password::sendResetLink(['email' => $user->email]);

        $status === Password::RESET_LINK_SENT
            ? Flux::toast(variant: 'success', text: "Tautan reset password dikirim ke {$user->email}.")
            : Flux::toast(variant: 'danger', text: __($status));
    }

    public function resetFilter(): void
    {
        $this->reset(['cari', 'role', 'kecamatan', 'aktif']);
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman judul="Pengguna" eyebrow="Administrasi" deskripsi="Akun petugas (Admin BPN, Petugas BPN, Petugas KUA) dan Nazir.">
        <flux:button variant="primary" icon="user-plus" :href="route('admin.pengguna.create')" wire:navigate>Tambah Petugas</flux:button>
    </x-admin.judul-halaman>

    <div class="kartu grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <flux:input wire:model.live.debounce.400ms="cari" icon="magnifying-glass" placeholder="Cari nama, email, atau username" aria-label="Cari" />
        </div>
        <flux:select wire:model.live="role" aria-label="Filter role">
            <flux:select.option value="">Semua role</flux:select.option>
            @foreach (\App\Enums\Role::cases() as $r)
                <flux:select.option value="{{ $r->value }}">{{ $r->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="kecamatan" aria-label="Filter kecamatan">
            <flux:select.option value="">Semua kecamatan</flux:select.option>
            @foreach ($this->kecamatans as $k)
                <flux:select.option value="{{ $k->id }}">{{ $k->nama }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="aktif" aria-label="Filter status">
            <flux:select.option value="">Semua status</flux:select.option>
            <flux:select.option value="1">Aktif</flux:select.option>
            <flux:select.option value="0">Nonaktif</flux:select.option>
        </flux:select>
    </div>

    <div class="kartu overflow-hidden">
        @if ($this->pengguna->isEmpty())
            <x-empty-state icon="users" title="Tidak ada pengguna" description="Coba ubah kata kunci atau filter.">
                <flux:button size="sm" variant="ghost" wire:click="resetFilter">Reset filter</flux:button>
            </x-empty-state>
        @else
            <flux:table :paginate="$this->pengguna" class="[&_td]:px-3 [&_th]:px-3">
                <flux:table.columns>
                    <flux:table.column>Nama</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Kecamatan</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->pengguna as $u)
                        <flux:table.row :key="'user-'.$u->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :initials="$u->initials()" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-primary-900">{{ $u->name }} @if ($u->id === auth()->id()) <span class="text-xs text-zinc-500">(Anda)</span> @endif</p>
                                        <p class="text-xs text-zinc-500">{{ $u->email }}@if ($u->username) · {{ '@'.$u->username }}@endif</p>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $u->peran()?->label() ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $u->peran() === \App\Enums\Role::Kua ? ($u->kecamatan?->nama ?? '—') : '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($u->is_active)
                                    <flux:badge size="sm" color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">Nonaktif</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Aksi untuk {{ $u->name }}" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" :href="route('admin.pengguna.edit', $u)" wire:navigate>Ubah</flux:menu.item>
                                        <flux:menu.item icon="key" wire:click="kirimResetPassword({{ $u->id }})"
                                            wire:confirm="Kirim tautan reset password ke {{ $u->email }}?">Kirim tautan reset password</flux:menu.item>
                                        @can('toggleAktif', $u)
                                            <flux:menu.separator />
                                            @if ($u->is_active)
                                                <flux:menu.item icon="no-symbol" variant="danger" wire:click="toggleAktif({{ $u->id }})"
                                                    wire:confirm="Nonaktifkan akun {{ $u->name }}? Pengguna tidak akan bisa login.">Nonaktifkan</flux:menu.item>
                                            @else
                                                <flux:menu.item icon="check-circle" wire:click="toggleAktif({{ $u->id }})">Aktifkan</flux:menu.item>
                                            @endif
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
