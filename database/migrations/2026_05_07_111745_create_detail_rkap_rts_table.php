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
        Schema::create('detail_rkap_rts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkap_rt_id')->constrained('rkap_rts')->onDelete('cascade');
            $table->TinyInteger('periode');
            $table->bigInteger('plan')->default(0);
            $table->bigInteger('actual')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_rkap_rts');
    }
};
