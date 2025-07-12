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
        Schema::table('datasheets', function (Blueprint $table) {
            $table->date('date_datasheet')->nullable()->after('datasheet_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasheets', function (Blueprint $table) {
            $table->dropColumn('date_datasheet');
        });
    }
};
