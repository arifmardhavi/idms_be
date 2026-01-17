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
        Schema::table('skhps', function (Blueprint $table) {
            $table->dropForeign(['plo_id']);
            $table->dropColumn('plo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skhps', function (Blueprint $table) {
            $table->foreignId('plo_id')
                  ->after('id')
                  ->constrained('plos')
                  ->onDelete('cascade');
        });
    }
};
