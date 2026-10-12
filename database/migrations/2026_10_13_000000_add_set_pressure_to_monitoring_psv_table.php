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
        Schema::table('monitoring_psv', function (Blueprint $table) {
            $table->string('set_pressure', 50)->nullable()->after('pid_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_psv', function (Blueprint $table) {
            $table->dropColumn('set_pressure');
        });
    }
};
