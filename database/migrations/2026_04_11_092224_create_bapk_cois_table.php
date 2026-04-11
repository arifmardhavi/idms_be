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
        Schema::create('bapk_cois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coi_id')->constrained()->onDelete('cascade'); // Relation to cois
            $table->text('bapk_coi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bapk_cois');
    }
};
