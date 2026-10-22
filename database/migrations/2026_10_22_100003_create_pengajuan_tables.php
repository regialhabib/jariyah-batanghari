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
        Schema::create('persyaratans', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_pengajuan', 30)->index();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('wajib')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable()->unique();
            $table->string('jenis', 30)->index();
            $table->foreignId('tanah_wakaf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nazir_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('draf')->index();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->foreignId('pemeriksa_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_pemeriksa')->nullable();
            $table->timestamps();
        });

        Schema::create('pengajuan_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persyaratan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kategori', 20)->default('persyaratan');
            $table->string('path');
            $table->string('nama_asli');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('ukuran')->default(0);
            $table->string('status_validasi', 15)->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('riwayat_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('dari_status', 20)->nullable();
            $table->string('ke_status', 20);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengajuans');
        Schema::dropIfExists('pengajuan_dokumens');
        Schema::dropIfExists('pengajuans');
        Schema::dropIfExists('persyaratans');
    }
};
