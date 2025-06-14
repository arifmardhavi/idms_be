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
        Schema::create('ga_drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_data_id')->constrained('engineering_data')->onDelete('cascade');
            $table->string('drawing_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_drawings');
    }
};
