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
        Schema::create('amandemen_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_new_id')->constrained('contract_news')->onDelete('cascade');
            $table->bigInteger('contract_price_before_amandemen')->nullable();
            $table->text('ba_agreement_file')->nullable();
            $table->text('result_amandemen_file')->nullable();
            $table->text('principle_permit_file')->nullable();
            $table->bigInteger('amandemen_price')->nullable();
            $table->date('amandemen_end_date')->nullable();
            $table->tinyInteger('amandemen_penalty')->default(0);
            $table->text('amandemen_termin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amandemen_news');
    }
};
