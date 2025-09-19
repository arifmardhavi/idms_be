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
        Schema::create('po_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('readiness_material_id')->constrained('readiness_materials')->onDelete('cascade');
            $table->bigInteger('no_po');
            $table->text('po_file');
            $table->date('delivery_date');
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
        Schema::dropIfExists('po_materials');
    }
};
