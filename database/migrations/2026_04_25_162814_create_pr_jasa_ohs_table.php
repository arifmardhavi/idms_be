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
        Schema::create('pr_jasa_ohs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_jasa_oh_id')->constrained('readiness_jasa_ohs')->onDelete('cascade');
            $table->bigInteger('no_pr')->nullable();
            $table->date('target_date')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_jasa_ohs');
    }
};
