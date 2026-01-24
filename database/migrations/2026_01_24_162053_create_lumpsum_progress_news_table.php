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
        Schema::create('lumpsum_progress_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_new_id')->constrained()->onDelete('cascade'); // Relation to contract_news
            $table->integer('week')->unsigned();
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
        Schema::dropIfExists('lumpsum_progress_news');
    }
};
