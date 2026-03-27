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
        Schema::table('amandemen_news', function (Blueprint $table) {
            $table->date('contract_end_date_before_amandemen')->nullable()->after('contract_price_before_amandemen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amandemen_news', function (Blueprint $table) {
            $table->dropColumn('contract_end_date_before_amandemen');
        });
    }
};
