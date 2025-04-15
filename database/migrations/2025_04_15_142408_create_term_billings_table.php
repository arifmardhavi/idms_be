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
        Schema::create('term_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termin_id')->constrained()->onDelete('cascade'); // Relation to contracts
            $table->string('billing_value');
            $table->string('payment_document');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_billings');
    }
};
