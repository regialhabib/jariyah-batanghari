<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tanah_wakafs', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->foreignId('nazir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_nazir')->nullable();
            $table->string('wakif_nama');
            $table->char('wakif_nik', 16)->nullable();
            $table->foreignId('desa_id')->constrained()->restrictOnDelete();
            $table->text('alamat')->nullable();
            $table->decimal('luas_m2', 12, 2);
            $table->string('jenis_penggunaan', 20)->index();
            $table->string('status_sertifikat', 10)->default('belum')->index();
            $table->string('nomor_aiw')->nullable();
            $table->date('tanggal_aiw')->nullable();
            $table->string('nomor_sertifikat')->nullable();
            $table->date('tanggal_sertifikat')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('batas_geojson')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->string('sumber', 10)->default('pengajuan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanah_wakafs');
    }
};
