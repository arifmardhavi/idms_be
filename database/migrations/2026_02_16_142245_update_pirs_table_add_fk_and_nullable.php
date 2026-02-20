<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pirs', function (Blueprint $table) {

            // Ubah pir_file jadi nullable
            $table->text('pir_file')->nullable()->change();

            // Tambah kolom hanya jika belum ada
            if (!Schema::hasColumn('pirs', 'historical_memorandum_id')) {
                $table->unsignedBigInteger('historical_memorandum_id')
                      ->nullable()
                      ->after('tanggal_pir');
            }

            // Tambah foreign key (cek dulu supaya tidak duplicate)
            $table->foreign('historical_memorandum_id')
                  ->references('id')
                  ->on('historical_memorandum')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pirs', function (Blueprint $table) {

            // Drop FK kalau ada
            $table->dropForeign(['historical_memorandum_id']);

            // Drop column kalau ada
            if (Schema::hasColumn('pirs', 'historical_memorandum_id')) {
                $table->dropColumn('historical_memorandum_id');
            }

            // Balikin pir_file jadi NOT NULL
            $table->text('pir_file')->nullable(false)->change();
        });
    }
};
