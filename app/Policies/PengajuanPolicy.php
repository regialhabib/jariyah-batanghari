<?php

namespace App\Policies;

use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use App\Models\User;

class PengajuanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNazir() || $user->isPetugas();
    }

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->isNazir()) {
            return $pengajuan->nazir_id === $user->id;
        }

        if ($user->isKua()) {
            return $this->sekecamatan($user, $pengajuan);
        }

        return $user->isBpn();
    }

    public function create(User $user): bool
    {
        return $user->isNazir();
    }

    /**
     * Nazir pemilik boleh mengubah data/dokumen selama status draf atau perlu perbaikan.
     */
    public function update(User $user, Pengajuan $pengajuan): bool
    {
        return $user->isNazir()
            && $pengajuan->nazir_id === $user->id
            && $pengajuan->status->bisaDiubahNazir();
    }

    public function kirim(User $user, Pengajuan $pengajuan): bool
    {
        return $this->update($user, $pengajuan);
    }

    public function delete(User $user, Pengajuan $pengajuan): bool
    {
        return $user->isNazir()
            && $pengajuan->nazir_id === $user->id
            && $pengajuan->status === StatusPengajuan::Draf;
    }

    /**
     * Memeriksa pengajuan: AIW oleh KUA kecamatan lokasi tanah, sertifikat hanya oleh BPN.
     */
    public function periksa(User $user, Pengajuan $pengajuan): bool
    {
        $berwenang = collect($pengajuan->jenis->pemeriksa())
            ->contains(fn ($role) => $user->hasRole($role->value));

        if (! $berwenang) {
            return false;
        }

        return $user->isKua() ? $this->sekecamatan($user, $pengajuan) : true;
    }

    private function sekecamatan(User $user, Pengajuan $pengajuan): bool
    {
        return $user->kecamatan_id !== null
            && $pengajuan->tanahWakaf?->desa?->kecamatan_id === $user->kecamatan_id;
    }
}
