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
        Schema::create('amandemens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->text('ba_agreement_file');
            $table->text('result_amandemen_file');
            $table->text('principle_permit_file')->nullable();
            $table->bigInteger('amandemen_price')->nullable();
            $table->date('amandemen_end_date')->nullable();
            $table->bigInteger('amandemen_penalty')->default(0);
            $table->text('amandemen_termin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amandemens');
    }
};
