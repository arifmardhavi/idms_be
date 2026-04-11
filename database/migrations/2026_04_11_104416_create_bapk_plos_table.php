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
        Schema::create('bapk_plos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plo_id')->constrained()->onDelete('cascade'); // Relation to plos
            $table->text('bapk_plo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bapk_plos');
    }
};
