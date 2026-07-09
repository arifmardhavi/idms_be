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
        Schema::table('tag_numbers', function (Blueprint $table) {
            $table->char('criticality', 2)->nullable()->after('tag_number');
            $table->char('sece', 1)->nullable()->after('tag_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tag_numbers', function (Blueprint $table) {
            $table->dropColumn('criticality');
            $table->dropColumn('sece');
        });
    }
};
