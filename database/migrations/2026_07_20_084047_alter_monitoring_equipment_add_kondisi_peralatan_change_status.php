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
        Schema::table('monitoring_equipment', function (Blueprint $table) {

            $table->string('kondisi_peralatan')
                ->nullable()
                ->after('tag_number_id');

            $table->string('status')
                ->nullable()
                ->change();

        });

        Schema::table('monitoring_equipment_logs', function (Blueprint $table) {

            $table->string('kondisi_peralatan')
                ->nullable()
                ->after('tag_number_id');

            $table->string('status')
                ->nullable()
                ->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_equipment', function (Blueprint $table) {

            $table->dropColumn('kondisi_peralatan');

            $table->char('status', 2)
                ->nullable()
                ->change();

        });

        Schema::table('monitoring_equipment_logs', function (Blueprint $table) {

            $table->dropColumn('kondisi_peralatan');

            $table->char('status', 2)
                ->nullable()
                ->change();

        });
    }
};