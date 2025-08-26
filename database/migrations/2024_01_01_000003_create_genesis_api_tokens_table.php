<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genesis_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->index();
            $table->string('user_id')->nullable()->index();
            $table->string('token_hash')->unique();
            $table->string('name')->nullable();
            $table->json('scopes')->nullable(); // permissions/scopes for the token
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'user_id']);
            $table->index(['expires_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genesis_api_tokens');
    }
};


