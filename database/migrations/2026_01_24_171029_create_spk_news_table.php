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
        Schema::create('spk_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_new_id')->constrained()->onDelete('cascade'); // Relation to contracts
            $table->string('no_spk');
            $table->string('spk_name');
            $table->date('spk_start_date');
            $table->date('spk_end_date');
            $table->bigInteger('spk_price');
            $table->string('spk_file');
            $table->tinyInteger('spk_status')->default(0);
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
        Schema::dropIfExists('spk_news');
    }
};
