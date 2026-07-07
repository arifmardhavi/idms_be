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
        Schema::create('monitoring_equipment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_number_id')->constrained('tag_numbers')->onDelete('cascade');
            $table->char('criticality', 2)->nullable();
            $table->char('sece', 1)->nullable();
            $table->char('status', 2)->nullable();
            $table->string('jenis_kerusakan')->nullable();
            $table->string('penyebab')->nullable();
            $table->string('penanganan_sementara')->nullable();
            $table->string('perbaikan_permanen')->nullable();
            $table->string('progress_perbaikan_permanen')->nullable();
            $table->string('kendala_perbaikan')->nullable();
            $table->bigInteger('estimasi_perbaikan')->nullable();
            $table->string('target')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_equipment_logs');
    }
};
