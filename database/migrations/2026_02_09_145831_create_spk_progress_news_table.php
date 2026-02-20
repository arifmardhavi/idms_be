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
        Schema::create('spk_progress_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_new_id')->constrained('spk_news')->onDelete('cascade');
            $table->tinyInteger('week')->unsigned();
            $table->decimal('plan', 3, 2);
            $table->decimal('actual', 3, 2);
            $table->string('progress_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk_progress_news');
    }
};
