<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Form Pengguna')] class extends Component {
    public ?User $user = null;

    public function mount(?User $user = null): void
    {
        $this->user = $user?->exists ? $user : null;
        $this->user ? $this->authorize('update', $this->user) : $this->authorize('create', User::class);
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">{{ $user ? 'Ubah' : 'Tambah' }} Pengguna</flux:heading>

    <div class="kartu">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
