<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add owner_id to properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->restrictOnDelete();
        });

        // 2. Add invited_by to invitations table
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreignId('invited_by')
                ->nullable()
                ->after('room_id')
                ->constrained('users')
                ->restrictOnDelete();
        });

        // 3. Backfill existing properties and invitations if owner exists
        $ownerId = DB::table('users')->where('role', 'owner')->value('id')
            ?? DB::table('users')->value('id');

        if ($ownerId) {
            DB::table('properties')->whereNull('owner_id')->update(['owner_id' => $ownerId]);
            DB::table('invitations')->whereNull('invited_by')->update(['invited_by' => $ownerId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
