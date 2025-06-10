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
        Schema::create('lampiran_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historical_memorandum_id')->constrained('historical_memorandum')->onDelete('cascade'); // Relation to plos
            $table->text('lampiran_memo'); // Column for memo attachment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lampiran_memos');
    }
};
