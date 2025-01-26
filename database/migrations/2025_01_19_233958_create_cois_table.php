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
        Schema::create('cois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plo_id')->constrained()->onDelete('cascade'); // Relation to plos
            $table->foreignId('tag_number_id')->constrained()->onDelete('cascade'); // Relation to tag_numbers
            $table->string('no_certificate');
            $table->date('issue_date');
            $table->date('overdue_date');
            $table->text('coi_certificate')->nullable();
            $table->text('coi_old_certificate')->nullable();
            $table->integer('rla')->default('0');
            $table->date('rla_issue')->nullable();
            $table->date('rla_overdue')->nullable();
            $table->text('rla_certificate')->nullable();
            $table->text('rla_old_certificate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cois');
    }
};
