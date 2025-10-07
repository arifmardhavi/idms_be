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
        Schema::create('readiness_jasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_readiness_id')->constrained('event_readinesses')->onDelete('cascade');
            $table->string('jasa_name');
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readiness_jasas');
    }
};
