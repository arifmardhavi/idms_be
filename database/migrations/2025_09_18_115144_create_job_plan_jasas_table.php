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
        Schema::create('job_plan_jasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_jasa_id')->constrained('readiness_jasas')->onDelete('cascade');
            $table->bigInteger('no_wo');
            $table->text('kak_file')->nullable();
            $table->text('boq_file')->nullable();
            $table->bigInteger('durasi_preparation');
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
        Schema::dropIfExists('job_plan_jasas');
    }
};
