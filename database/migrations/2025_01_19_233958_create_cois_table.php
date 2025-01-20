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
            $table->foreignId('tag_number_id')->constrained()->onDelete('cascade'); // Relation to types
            $table->string('no_certificate');
            $table->text('coi_certificate');
            $table->date('issue_date');
            $table->date('overdue_date');
            $table->integer('rla')->default('0');
            $table->date('rla_issue')->nullable();
            $table->date('rla_overdue')->nullable();
            $table->text('file_rla')->nullable();
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
