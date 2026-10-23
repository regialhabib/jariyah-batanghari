<?php

use App\Rules\NomorBelumDipakai;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor AIW dan nomor sertifikat tidak boleh ganda. Validasi aplikasi ({@see NomorBelumDipakai})
 * memberi pesan yang ramah; indeks ini lapisan terakhir bila dua petugas menyimpan nomor sama bersamaan.
 * Kolom tetap nullable, jadi tanah yang belum punya nomor tidak terpengaruh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah_wakafs', function (Blueprint $table) {
            $table->unique('nomor_aiw');
            $table->unique('nomor_sertifikat');
        });
    }

    public function down(): void
    {
        Schema::table('tanah_wakafs', function (Blueprint $table) {
            $table->dropUnique(['nomor_aiw']);
            $table->dropUnique(['nomor_sertifikat']);
        });
    }
};
