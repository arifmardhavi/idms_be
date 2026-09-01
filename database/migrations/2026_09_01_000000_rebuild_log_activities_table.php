<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('log_activities');

        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('action', [
                'login',
                'logout',
                'visit',
                'view',
                'download',
                'export',
                'create',
                'update',
                'delete',
                'import',
            ]);
            $table->string('module', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_label', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
        });

        Schema::dropIfExists('open_file_activities');
    }

    public function down(): void
    {
        Schema::dropIfExists('log_activities');

        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('module');
            $table->string('action');
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
};
