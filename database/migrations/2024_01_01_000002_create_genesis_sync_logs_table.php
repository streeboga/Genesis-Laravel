<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genesis_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->index();
            $table->string('data_type'); // users, billing, features, etc.
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('records_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'data_type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genesis_sync_logs');
    }
};






