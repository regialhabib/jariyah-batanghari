<?php

namespace App\Notifications;

use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusPengajuanBerubah extends Notification
{
    use Queueable;

    public function __construct(
        public Pengajuan $pengajuan,
        public StatusPengajuan $statusBaru,
        public ?string $catatan = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Status Pengajuan '.$this->pengajuan->nomor.': '.$this->statusBaru->label())
            ->greeting('Assalamu\'alaikum, '.$notifiable->name)
            ->line('Status pengajuan '.$this->pengajuan->jenis->labelSingkat().' untuk "'.$this->pengajuan->tanahWakaf->nama.'" berubah menjadi: '.$this->statusBaru->label().'.');

        if ($this->catatan) {
            $mail->line('Catatan petugas: '.$this->catatan);
        }

        return $mail->action('Lihat Pengajuan', route('nazir.pengajuan.show', $this->pengajuan));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pengajuan_id' => $this->pengajuan->id,
            'nomor' => $this->pengajuan->nomor,
            'tanah' => $this->pengajuan->tanahWakaf->nama,
            'status' => $this->statusBaru->value,
            'judul' => 'Pengajuan '.$this->statusBaru->label(),
            'pesan' => $this->pengajuan->tanahWakaf->nama.' — '.$this->statusBaru->label(),
            'catatan' => $this->catatan,
        ];
    }
}
