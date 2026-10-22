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
        Schema::create('artikels', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('ikon', 50)->default('book-open');
            $table->text('ringkasan');
            $table->longText('isi');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('panduan_langkahs', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan');
            $table->text('jawaban');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('unduhans', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('keterangan')->nullable();
            $table->string('file')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedInteger('jumlah_unduh')->default(0);
            $table->timestamps();
        });

        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();
            $table->text('nilai')->nullable();
            $table->string('grup', 20)->default('umum');
            $table->timestamps();
        });

        Schema::create('pesans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kontak');
            $table->text('isi');
            $table->string('ip', 45)->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesans');
        Schema::dropIfExists('pengaturans');
        Schema::dropIfExists('unduhans');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('panduan_langkahs');
        Schema::dropIfExists('artikels');
    }
};
