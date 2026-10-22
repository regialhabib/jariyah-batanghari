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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role', 20)->default('nazir')->index()->after('username');
            $table->char('nik', 16)->nullable()->unique()->after('username');
            $table->string('phone', 20)->nullable()->after('email');
            $table->foreignId('kecamatan_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('kecamatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kecamatan_id');
            $table->dropColumn(['username', 'role', 'nik', 'phone', 'is_active']);
        });
    }
};
