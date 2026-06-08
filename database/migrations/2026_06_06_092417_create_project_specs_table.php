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
        Schema::create('project_specs', function (Blueprint $table) {
            $table->id();
            $table->string('no_project_spec')->nullable();
            $table->string('judul');
            $table->date('tanggal_project_spec');
            $table->text('project_spec_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_specs');
    }
};
