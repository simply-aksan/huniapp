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
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->text('question');
            $table->text('answer');
            $table->json('sources')->nullable();
            $table->boolean('is_fallback')->default(false)->index();
            $table->decimal('relevance_score', 4, 3)->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};
