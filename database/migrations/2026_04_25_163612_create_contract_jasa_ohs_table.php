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
        Schema::create('contract_jasa_ohs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_jasa_oh_id')->constrained('readiness_jasa_ohs')->onDelete('cascade');
            $table->foreignId('contract_new_id')->nullable()->constrained('contract_news')->onDelete('set null');
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_jasa_ohs');
    }
};
