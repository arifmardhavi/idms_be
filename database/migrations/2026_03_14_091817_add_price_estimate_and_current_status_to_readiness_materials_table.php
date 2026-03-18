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
        Schema::table('readiness_materials', function (Blueprint $table) {
            $table->bigInteger('price_estimate')->nullable()->after('material_name');
            $table->text('current_status')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readiness_materials', function (Blueprint $table) {
            $table->dropColumn(['price_estimate', 'current_status']);
        });
    }
};
