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
        Schema::create('historical_memorandum', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tag_number_id')->nullable(); // Relation to tag_numbers
            $table->string('judul_memorandum');
            $table->char('jenis_memorandum', 1)->default(0)->comment('0 = Rekomendasi, 1 = Laporan Pekerjaan');
            $table->char('jenis_pekerjaan', 1)->default(0)->comment('0 = TA, 1 = RUTIN, 2 = NON RUTIN, 3 = OVERHAUL');
            $table->text('memorandum_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_memorandum');
    }
};
