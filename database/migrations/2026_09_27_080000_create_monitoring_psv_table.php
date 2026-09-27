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
        Schema::create('monitoring_psv', function (Blueprint $table) {

            $table->id();

            /**
             * Anchor ke COI. Tag Number & Masa Berlaku TIDAK disimpan di sini,
             * keduanya dibaca live dari tabel `cois` (dan `tag_numbers`).
             * Cascade delete: kalau COI dihapus, baris monitoring ikut hilang.
             */
            $table->foreignId('coi_id')
                ->constrained('cois')
                ->cascadeOnDelete();

            /**
             * Kolom inputan manual user.
             */
            $table->string('status_redundant', 50)->nullable();

            $table->string('pid_no', 50)->nullable();

            $table->string('kategori', 50)->nullable();

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->unique('coi_id');
            $table->index('kategori');
            $table->index('status_redundant');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_psv');
    }
};
