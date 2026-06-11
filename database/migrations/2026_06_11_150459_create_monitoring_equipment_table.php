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
        Schema::create('monitoring_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_number_id')->constrained('tag_numbers')->onDelete('cascade');
            $table->string('criticality')->nullable();
            $table->char('sece', 1)->nullable();
            $table->char('status', 2)->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->string('target')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_equipment');
    }
};
