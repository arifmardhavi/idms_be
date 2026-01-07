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
        Schema::create('report_izin_operasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('izin_operasi_id')->constrained()->onDelete('cascade'); // Relation to izin_operasis
            $table->text('report_izin_operasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_izin_operasis');
    }
};
