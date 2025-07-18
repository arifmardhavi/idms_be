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
        Schema::table('spk_progresses', function (Blueprint $table) {
            $table->decimal('plan_progress', 5, 2)->change();
            $table->decimal('actual_progress', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk_progresses', function (Blueprint $table) {
            $table->integer('plan_progress')->change();
            $table->integer('actual_progress')->change();
        });
    }
};
