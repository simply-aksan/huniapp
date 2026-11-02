<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Inventory;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('database session table exists and stores sessions', function () {
    expect(Schema::hasTable('sessions'))->toBeTrue();

    // Verify session columns
    expect(Schema::hasColumns('sessions', [
        'id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity',
    ]))->toBeTrue();

    // Simulate session storage
    $sessionId = 'test_session_'.uniqid();
    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit/Pest Test Agent',
        'payload' => base64_encode(serialize(['huni_test' => 'verified'])),
        'last_activity' => time(),
    ]);

    $storedSession = DB::table('sessions')->where('id', $sessionId)->first();
    expect($storedSession)->not->toBeNull()
        ->and($storedSession->ip_address)->toBe('127.0.0.1');

    // Clean up test session record
    DB::table('sessions')->where('id', $sessionId)->delete();
});

test('http requests persist session to mysql sessions table', function () {
    $response = $this->withSession(['user_test_key' => 'huni_session_active'])->get(route('login'));
    $response->assertStatus(200);

    // Verify session record exists in MySQL database
    $sessionCount = DB::table('sessions')->count();
    expect($sessionCount)->toBeGreaterThanOrEqual(1);
});

test('all required HUNI tables exist in database', function () {
    $expectedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'properties',
        'rooms',
        'facilities',
        'facility_property',
        'facility_room',
        'inventory',
        'invitations',
        'contracts',
        'contract_documents',
        'bills',
        'bill_items',
        'payments',
        'payment_proofs',
        'complaints',
        'complaint_replies',
    ];

    foreach ($expectedTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist.");
    }
});

test('user model supports owner and tenant roles with profile fields', function () {
    $owner = User::where('email', 'aksan@huniapp.test')->first();
    $tenant = User::where('email', 'budi@huniapp.test')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->role)->toBe(UserRole::Owner)
        ->and($owner->isOwner())->toBeTrue()
        ->and($owner->isTenant())->toBeFalse();

    expect($tenant)->not->toBeNull()
        ->and($tenant->role)->toBe(UserRole::Tenant)
        ->and($tenant->isTenant())->toBeTrue()
        ->and($tenant->isOwner())->toBeFalse();
});

test('property and room relationships load correctly', function () {
    $owner = User::where('email', 'aksan@huniapp.test')->first();
    $property = Property::where('slug', 'kos-harmoni')->first();
    expect($property)->not->toBeNull()
        ->and($property->owner_id)->toBe($owner->id)
        ->and($property->owner->id)->toBe($owner->id)
        ->and($property->isOwnedBy($owner))->toBeTrue();

    expect($owner->properties->pluck('id'))->toContain($property->id);

    $rooms = $property->rooms;
    expect($rooms->count())->toBeGreaterThanOrEqual(2);

    $roomA3 = $rooms->firstWhere('room_number', 'A3');
    expect($roomA3)->not->toBeNull()
        ->and($roomA3->property->id)->toBe($property->id)
        ->and($roomA3->price)->toBe('2000000.00');
});

test('facility and pivot relationships load correctly', function () {
    $property = Property::where('slug', 'kos-harmoni')->first();
    $roomA3 = Room::where('room_number', 'A3')->first();

    expect($property->facilities->pluck('name'))->toContain('WiFi');
    expect($roomA3->facilities->pluck('name'))->toContain('AC');
});

test('inventory and room relationship loads correctly', function () {
    $item = Inventory::where('name', 'AC Daikin 1 PK')->first();

    expect($item)->not->toBeNull()
        ->and($item->property)->not->toBeNull()
        ->and($item->room)->not->toBeNull()
        ->and($item->room->room_number)->toBe('A3');
});

test('invitation model loads target room and inviting owner correctly', function () {
    $owner = User::where('email', 'aksan@huniapp.test')->first();
    $invitation = Invitation::where('token', 'inv-token-demo-123456')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->invited_by)->toBe($owner->id)
        ->and($invitation->sender->id)->toBe($owner->id)
        ->and($invitation->invitedBy->id)->toBe($owner->id)
        ->and($invitation->room)->not->toBeNull()
        ->and($invitation->room->room_number)->toBe('B1');

    expect($owner->sentInvitations->pluck('id'))->toContain($invitation->id);
});

test('contract relationships with tenant, room, and documents load correctly', function () {
    $contract = Contract::where('contract_number', 'KT-2025-001')->first();

    expect($contract)->not->toBeNull()
        ->and($contract->user->email)->toBe('budi@huniapp.test')
        ->and($contract->room->room_number)->toBe('A3')
        ->and($contract->documents->count())->toBeGreaterThanOrEqual(1)
        ->and($contract->documents->first()->file_name)->toBe('Kontrak_KT-2025-001.pdf');
});

test('financial models: bills, items, payments, and proofs load correctly', function () {
    $bill = Bill::where('bill_number', 'INV-2025-032')->first();

    expect($bill)->not->toBeNull()
        ->and($bill->items->count())->toBeGreaterThanOrEqual(1)
        ->and($bill->payments->count())->toBeGreaterThanOrEqual(1);

    $payment = $bill->payments->first();
    expect($payment->verifier)->not->toBeNull()
        ->and($payment->verifier->email)->toBe('aksan@huniapp.test')
        ->and($payment->proofs->count())->toBeGreaterThanOrEqual(1)
        ->and($payment->proofs->first()->file_name)->toBe('bukti_transfer_feb2026.jpg');
});

test('operations models: complaints and replies load correctly', function () {
    $complaint = Complaint::where('ticket_number', 'KMP-2026-0004')->first();

    expect($complaint)->not->toBeNull()
        ->and($complaint->user->email)->toBe('budi@huniapp.test')
        ->and($complaint->room->room_number)->toBe('A3')
        ->and($complaint->replies->count())->toBeGreaterThanOrEqual(1)
        ->and($complaint->replies->first()->user->email)->toBe('aksan@huniapp.test');
});

test('foreign key restrictOnDelete prevents accidental data loss', function () {
    $tenant = User::where('email', 'budi@huniapp.test')->first();

    // Attempting to delete a user with an active contract must throw QueryException
    expect(function () use ($tenant) {
        $tenant->delete();
    })->toThrow(QueryException::class);
});
