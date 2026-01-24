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
        Schema::create('termin_receipt_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termin_new_id')->constrained()->onDelete('cascade'); // Relation to termin_news
            $table->bigInteger('receipt_nominal')->unsigned();
            $table->string('receipt_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termin_receipt_news');
    }
};
