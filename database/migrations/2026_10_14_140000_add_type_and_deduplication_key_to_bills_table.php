<?php

declare(strict_types=1);

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
        Schema::table('bills', function (Blueprint $table) {
            $table->string('type')->default('sewa')->after('room_id')->index();
            $table->string('deduplication_key')->nullable()->after('type')->unique('bills_deduplication_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropUnique('bills_deduplication_key_unique');
            $table->dropColumn(['deduplication_key', 'type']);
        });
    }
};
