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
        Schema::create('po_material_ohs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_material_oh_id')->constrained('readiness_material_ohs')->onDelete('cascade');
            $table->foreignId('contract_new_id')->nullable()->constrained('contract_news')->onDelete('set null');
            $table->bigInteger('no_po')->nullable();
            $table->date('delivery_date')->nullable();
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
        Schema::dropIfExists('po_material_ohs');
    }
};
