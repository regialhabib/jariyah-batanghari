<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Activitylog v4 (versi yang mendukung PHP 8.2) menyimpan nilai lama/baru di `properties`
 * dan butuh kolom `batch_uuid`. Log lama dari kolom v5 `attribute_changes` dipindahkan agar riwayat tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }

        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            return;
        }

        DB::table('activity_log')->whereNotNull('attribute_changes')->lazyById()->each(function (object $log): void {
            $perubahan = json_decode((string) $log->attribute_changes, true);

            if (! is_array($perubahan) || $perubahan === []) {
                return;
            }

            $properti = json_decode((string) $log->properties, true);

            DB::table('activity_log')->where('id', $log->id)->update([
                'properties' => json_encode([...(is_array($properti) ? $properti : []), ...$perubahan], JSON_UNESCAPED_UNICODE),
            ]);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('attribute_changes');
        });
    }
};
