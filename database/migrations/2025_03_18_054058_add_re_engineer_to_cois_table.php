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
        Schema::table('cois', function (Blueprint $table) {
            $table->text('re_engineer')->nullable()->after('rla_old_certificate');
            $table->text('re_engineer_certificate')->nullable()->after('re_engineer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->dropColumn(['re_engineer', 're_engineer_certificate']);
        });
    }
};
