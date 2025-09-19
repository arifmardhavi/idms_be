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
        Schema::create('rekomendasi_jasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_jasa_id')->constrained('readiness_jasas')->onDelete('cascade');
            $table->foreignId('historical_memorandum_id')->nullable()->constrained('historical_memorandum')->onDelete('set null');
            $table->text('rekomendasi_file')->nullable();
            $table->date('target_date');
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_jasas');
    }
};
