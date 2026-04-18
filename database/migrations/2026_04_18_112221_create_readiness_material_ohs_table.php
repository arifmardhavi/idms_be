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
        Schema::create('readiness_material_ohs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_readiness_oh_id')->constrained('event_readiness_ohs')->onDelete('cascade');
            $table->string('material_name');
            $table->bigInteger('price_estimate')->nullable();
            $table->integer('type')->default(0); // 0: LLDI, 1: Non LLDI
            $table->text('current_status')->nullable();
            $table->date('tanggal_target');
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readiness_material_ohs');
    }
};
