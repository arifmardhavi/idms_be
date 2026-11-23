<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_memorandum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_issue');
            $table->string('issue_operasional', 255);
            $table->string('tag_number_ids', 255)->nullable();
            $table->foreignId('nota_dinas')->nullable()
                ->constrained('historical_memorandum')
                ->nullOnDelete();
            $table->unsignedBigInteger('no_notif')->nullable();
            $table->string('status_notif', 20)->nullable();
            $table->unsignedBigInteger('no_wo')->nullable();
            $table->string('status_wo', 2)->nullable();
            $table->string('no_po', 150)->nullable();
            $table->string('status', 10)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('tanggal_issue');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_memorandum');
    }
};
