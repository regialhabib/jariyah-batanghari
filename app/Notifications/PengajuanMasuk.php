<?php

namespace App\Notifications;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Untuk petugas pemeriksa (KUA kecamatan / BPN) saat pengajuan dikirim atau dikirim ulang.
 */
class PengajuanMasuk extends Notification
{
    use Queueable;

    public function __construct(public Pengajuan $pengajuan) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pengajuan_id' => $this->pengajuan->id,
            'nomor' => $this->pengajuan->nomor,
            'judul' => 'Pengajuan masuk: '.$this->pengajuan->nomor,
            'pesan' => $this->pengajuan->jenis->labelSingkat().' — '.$this->pengajuan->tanahWakaf->nama,
        ];
    }
}
