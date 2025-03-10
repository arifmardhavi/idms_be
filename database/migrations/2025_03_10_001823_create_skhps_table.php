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
        Schema::create('skhps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plo_id')->constrained()->onDelete('cascade'); // Relation to plos
            $table->foreignId('tag_number_id')->constrained()->onDelete('cascade'); // Relation to tag_numbers
            $table->string('no_skhp');
            $table->date('issue_date');
            $table->date('overdue_date');
            $table->text('file_skhp')->nullable();
            $table->text('file_old_skhp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skhps');
    }
};
