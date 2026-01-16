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
        Schema::table('izin_usahas', function (Blueprint $table) {
            $table->string('no_izin_usaha')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izin_usahas', function (Blueprint $table) {
            $table->dropColumn('no_izin_usaha');
        });
    }
};
