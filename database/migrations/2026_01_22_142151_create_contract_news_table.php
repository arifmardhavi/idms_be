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
        Schema::create('contract_news', function (Blueprint $table) {
            $table->id();
            $table->string('no_vendor');
            $table->string('vendor_name');
            $table->string('no_contract');
            $table->string('contract_name');
            $table->tinyInteger('contract_type')->unsigned();
            $table->date('contract_date')->nullable();
            $table->bigInteger('contract_price');
            $table->text('contract_file');
            $table->text('current_status')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->text('meeting_notes')->nullable();
            $table->tinyInteger('pengawas')->unsigned();
            $table->tinyInteger('contract_status')->default(0)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_news');
    }
};
