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
        Schema::create('termin_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_new_id')->constrained()->onDelete('cascade'); // Relation to contract_news
            $table->string('termin');
            $table->string('description')->nullable();
            $table->bigInteger('receipt_nominal')->nullable();
            $table->string('receipt_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termin_news');
    }
};
