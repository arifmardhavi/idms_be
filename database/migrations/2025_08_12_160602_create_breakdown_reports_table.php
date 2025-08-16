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
        Schema::create('breakdown_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_inspection_id')->constrained('laporan_inspections')->onDelete('cascade');
            $table->string('judul');
            $table->date('breakdown_report_date');
            $table->foreignId('historical_memorandum_id')->nullable()->constrained('historical_memorandum')->onDelete('set null');
            $table->text('laporan_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breakdown_reports');
    }
};
