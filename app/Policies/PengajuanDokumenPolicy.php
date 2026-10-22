<?php

namespace App\Policies;

use App\Models\PengajuanDokumen;
use App\Models\User;

class PengajuanDokumenPolicy
{
    /**
     * Dokumen bisa dibuka oleh siapa pun yang boleh melihat pengajuannya.
     */
    public function view(User $user, PengajuanDokumen $dokumen): bool
    {
        return $user->can('view', $dokumen->pengajuan);
    }

    public function delete(User $user, PengajuanDokumen $dokumen): bool
    {
        return $user->can('update', $dokumen->pengajuan);
    }
}
