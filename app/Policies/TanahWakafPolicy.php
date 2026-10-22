<?php

namespace App\Policies;

use App\Models\TanahWakaf;
use App\Models\User;

class TanahWakafPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNazir() || $user->isPetugas();
    }

    public function view(User $user, TanahWakaf $tanah): bool
    {
        if ($user->isNazir()) {
            return $tanah->nazir_id === $user->id;
        }

        if ($user->isKua()) {
            return $user->kecamatan_id !== null && $tanah->desa?->kecamatan_id === $user->kecamatan_id;
        }

        return $user->isBpn();
    }

    public function create(User $user): bool
    {
        return $user->isBpn();
    }

    public function update(User $user, TanahWakaf $tanah): bool
    {
        return $user->isBpn();
    }

    public function delete(User $user, TanahWakaf $tanah): bool
    {
        return $user->isBpn() && ! $tanah->pengajuans()->exists();
    }

    public function impor(User $user): bool
    {
        return $user->isBpn();
    }
}
