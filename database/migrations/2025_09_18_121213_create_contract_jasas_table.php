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
        Schema::create('contract_jasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_jasa_id')->constrained('readiness_jasas')->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_jasas');
    }
};
