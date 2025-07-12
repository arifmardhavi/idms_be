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
        Schema::table('ga_drawings', function (Blueprint $table) {
            $table->date('date_drawing')->nullable()->after('drawing_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ga_drawings', function (Blueprint $table) {
            $table->dropColumn('date_drawing');
        });
    }
};
