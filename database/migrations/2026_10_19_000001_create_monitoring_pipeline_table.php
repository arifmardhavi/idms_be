<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_pipeline', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tag_number_id')
                ->constrained('tag_numbers')
                ->cascadeOnDelete();

            $table->string('tml_drawing_number', 150)->nullable();
            $table->integer('api_service_class')->nullable();
            $table->string('insulation', 10)->nullable();
            $table->string('grade_material', 150)->nullable();
            $table->double('nps')->nullable();
            $table->double('od')->nullable();
            $table->string('sch', 50)->nullable();
            $table->double('t_nom')->nullable();
            $table->double('operating_press')->nullable();
            $table->double('design_press')->nullable();
            $table->double('design_temp')->nullable();
            $table->double('s')->nullable();
            $table->double('e')->nullable();
            $table->double('w')->nullable();
            $table->double('y')->nullable();
            $table->double('min_req_thk')->nullable();
            $table->double('half_nom_thk')->nullable();
            $table->date('tanggal_pasang_line')->nullable();
            $table->double('actual_thk')->nullable();
            $table->date('tanggal_ut_terakhir')->nullable();
            $table->double('corrosion_rate')->nullable();
            $table->double('remaining_life')->nullable();
            $table->double('alert_structural_thickness')->nullable();
            $table->date('next_plan_inspection_api')->nullable();
            $table->date('used_plan_inspection')->nullable();
            $table->string('pipe_condition')->nullable();
            $table->date('estimated_end_of_life')->nullable();
            $table->string('maintenance_status')->nullable();
            $table->string('rencana_tindak_lanjut')->nullable();

            $table->timestamps();

            $table->unique('tag_number_id');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_pipeline');
    }
};
