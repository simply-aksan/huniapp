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
        Schema::create('bill_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->unique()->constrained('bills')->cascadeOnDelete();
            $table->decimal('risk_score', 5, 2);
            $table->string('risk_tier', 50)->index();
            $table->string('evaluator_type', 100)->default('heuristic');
            $table->string('model_version', 50)->default('1.0.0');
            $table->json('contributing_factors');
            $table->json('metadata')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_risk_scores');
    }
};
