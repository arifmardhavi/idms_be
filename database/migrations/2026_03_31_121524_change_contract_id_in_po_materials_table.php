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
        Schema::table('po_materials', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['contract_id']);

            // Drop kolom lama
            $table->dropColumn('contract_id');

            // Tambah kolom baru
            $table->foreignId('contract_new_id')
                ->nullable()
                ->after('readiness_material_id')
                ->constrained('contract_news')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_materials', function (Blueprint $table) {
            // rollback: hapus yang baru
            $table->dropForeign(['contract_new_id']);
            $table->dropColumn('contract_new_id');

            // balikin yang lama
            $table->foreignId('contract_id')
                ->nullable()
                ->constrained('contracts')
                ->onDelete('set null');
        });
    }
};
