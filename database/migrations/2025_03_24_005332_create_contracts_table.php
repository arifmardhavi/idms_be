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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('no_vendor');
            $table->string('vendor_name');
            $table->string('no_contract');
            $table->string('contract_name');
            $table->char('contract_type')->default('1');
            $table->date('contract_date');
            $table->integer('contract_price');
            $table->string('contract_file');
            $table->char('contract_status')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
