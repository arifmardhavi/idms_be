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
        Schema::create('spks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade'); // Relation to contracts
            $table->string('no_spk');
            $table->string('spk_name');
            $table->date('spk_start_date');
            $table->date('spk_end_date');
            $table->integer('spk_price');
            $table->string('spk_file');
            $table->char('spk_status')->default('1');
            $table->char('invoice')->default('0');
            $table->integer('invoice_value')->nullable();
            $table->string('invoice_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spks');
    }
};
