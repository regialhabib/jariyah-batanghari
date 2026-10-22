<?php

namespace Database\Factories;

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengajuan>
 */
class PengajuanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => JenisPengajuan::Aiw,
            'nazir_id' => User::factory()->nazir(),
            'tanah_wakaf_id' => fn (array $attr) => TanahWakaf::factory()->tanpaAiw()->create(['nazir_id' => $attr['nazir_id']])->id,
            'status' => StatusPengajuan::Draf,
        ];
    }

    public function jenis(JenisPengajuan $jenis): static
    {
        return $this->state(fn () => ['jenis' => $jenis]);
    }

    /**
     * Pengajuan yang sudah dikirim (punya nomor & tanggal pengajuan).
     */
    public function diajukan(): static
    {
        return $this->state(fn () => ['status' => StatusPengajuan::Diajukan])
            ->afterMaking(function (Pengajuan $p) {
                $p->nomor = Pengajuan::buatNomor();
                $p->diajukan_at = now();
            });
    }
}
