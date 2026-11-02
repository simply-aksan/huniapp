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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('room_number');
            $table->unsignedTinyInteger('floor')->default(1);
            $table->string('type')->default('Standard');
            $table->decimal('price', 12, 2);
            $table->string('size')->nullable();
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->string('status')->default('kosong')->index();
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
