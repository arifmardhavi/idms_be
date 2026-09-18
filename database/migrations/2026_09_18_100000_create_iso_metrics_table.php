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
        Schema::create('iso_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('no_drawing')->unique();
            $table->string('judul');
            $table->date('tanggal');
            $table->text('iso_metric_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iso_metrics');
    }
};