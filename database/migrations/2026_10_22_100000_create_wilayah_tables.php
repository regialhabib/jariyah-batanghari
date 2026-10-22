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
        Schema::create('kecamatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('kode', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('jenis', 10)->default('desa');
            $table->string('kode', 13)->nullable();
            $table->timestamps();

            $table->unique(['kecamatan_id', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desas');
        Schema::dropIfExists('kecamatans');
    }
};
