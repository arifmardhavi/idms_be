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
        Schema::table('contract_jasas', function (Blueprint $table) {
            $table->renameColumn('contract_id', 'contract_new_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_jasas', function (Blueprint $table) {
            $table->renameColumn('contract_new_id', 'contract_id');
        });
    }
};
