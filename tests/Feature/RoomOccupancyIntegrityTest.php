<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomOccupancyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

beforeEach(function () {
    $this->occupancyService = app(RoomOccupancyService::class);

    // Dynamic test owner
    $this->owner = User::create([
        'name' => 'Owner Testing Isolation',
        'email' => 'owner_isolation_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // Dynamic test property
    $this->property = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Property Testing Isolation '.Str::random(6),
        'slug' => 'prop-isolation-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Pengujian No. 1',
        'city' => 'Jakarta',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    // Tenant A & Tenant B
    $this->tenantA = User::create([
        'name' => 'Tenant A Isolation',
        'email' => 'tenant_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Tenant B Isolation',
        'email' => 'tenant_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

test('1. an accepted invitation with no corresponding contract reserves a room according to reservation rules', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    $this->occupancyService->syncRoomStatus($room);

    expect($room->fresh()->status)->toBe('terisi');
});

test('2. creating an active contract for the same tenant and room does not leave a phantom reservation after contract completion', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    // Active contract created for Tenant A
    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->toDateString(),
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    $this->occupancyService->syncRoomStatus($room);
    expect($room->fresh()->status)->toBe('terisi');

    // Contract finishes
    $contract->update(['status' => 'selesai']);
    $this->occupancyService->syncRoomStatus($room);

    // Room must now transition to kosong, NOT stuck as terisi by phantom reservation
    expect($room->fresh()->status)->toBe('kosong');
});

test('3. phantom reservation is prevented when contract is cancelled', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    // Contract cancelled
    $contract->update(['status' => 'dibatalkan']);
    $this->occupancyService->syncRoomStatus($room);

    expect($room->fresh()->status)->toBe('kosong');
});

test('4. an invitation for a different tenant is not incorrectly considered fulfilled by another tenants contract', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    // Tenant B has an unfulfilled accepted invitation
    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantB->id,
        'name' => $this->tenantB->name,
        'email' => $this->tenantB->email,
        'phone' => $this->tenantB->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    // Tenant A had a completed contract on this room
    Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'start_date' => now()->subMonths(12)->toDateString(),
        'end_date' => now()->subMonths(6)->toDateString(),
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => 'selesai',
    ]);

    $this->occupancyService->syncRoomStatus($room);

    // Tenant B's unfulfilled reservation must keep room terisi
    expect($room->fresh()->status)->toBe('terisi');
});

test('5. a room marked perbaikan remains perbaikan after occupancy synchronization', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'perbaikan',
    ]);

    $this->occupancyService->syncRoomStatus($room);

    expect($room->fresh()->status)->toBe('perbaikan');
});

test('6. a room marked perbaikan is protected when it has an accepted invitation', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'perbaikan',
    ]);

    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    $this->occupancyService->syncRoomStatus($room);

    // perbaikan MUST NOT be overwritten to terisi
    expect($room->fresh()->status)->toBe('perbaikan');
});

test('7. a room with an active contract retains expected occupancy status when not under repair', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    $this->occupancyService->syncRoomStatus($room);

    expect($room->fresh()->status)->toBe('terisi');
});

test('8. a room with another valid reservation remains occupied when a different contract ends', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    // Active contract for Tenant A
    $contractA = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantA->id,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->toDateString(),
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    // Valid pending reservation for Tenant B
    Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantB->id,
        'name' => $this->tenantB->name,
        'email' => $this->tenantB->email,
        'phone' => $this->tenantB->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    // Contract A completes
    $contractA->update(['status' => 'selesai']);
    $this->occupancyService->syncRoomStatus($room);

    // Room remains terisi because Tenant B is waiting for contract
    expect($room->fresh()->status)->toBe('terisi');
});

test('9. existing invitation acceptance and registration flows continue to work', function () {
    $room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'R-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    $token = Str::random(40);
    $invitation = Invitation::create([
        'invited_by' => $this->owner->id,
        'room_id' => $room->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => $token,
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    $response = $this->actingAs($this->tenantA)->post(route('invitations.accept', $token));

    $response->assertRedirect(route('tenant.dashboard'));
    $response->assertSessionHas('success');

    $invitation->refresh();
    expect($invitation->status)->toBe('diterima')
        ->and($invitation->user_id)->toBe($this->tenantA->id)
        ->and($room->fresh()->status)->toBe('terisi');
});

test('10. test database guard rejects unsafe configuration and permits only huniapp_testing', function () {
    // 1. Guard must abort if database points to production/development 'huniapp'
    expect(fn () => TestCase::validateDatabaseSafety('mysql', 'huniapp', 'huniapp'))
        ->toThrow(RuntimeException::class, 'CRITICAL DATABASE SAFETY VIOLATION');

    // 2. Guard must abort if database name is empty or missing
    expect(fn () => TestCase::validateDatabaseSafety('mysql', null, null))
        ->toThrow(RuntimeException::class, 'UNSAFE TEST DATABASE CONFIGURATION');

    expect(fn () => TestCase::validateDatabaseSafety('mysql', '', ''))
        ->toThrow(RuntimeException::class, 'UNSAFE TEST DATABASE CONFIGURATION');

    // 3. Guard must abort if database points to any unapproved database
    expect(fn () => TestCase::validateDatabaseSafety('mysql', 'random_db', 'random_db'))
        ->toThrow(RuntimeException::class, 'UNAPPROVED TEST DATABASE');

    // 4. Guard must allow strictly 'huniapp_testing'
    expect(fn () => TestCase::validateDatabaseSafety('mysql', 'huniapp_testing', 'huniapp_testing'))
        ->not->toThrow(RuntimeException::class);

    // 5. Verify active connected database is indeed huniapp_testing
    $activeDatabase = DB::connection()->getDatabaseName();
    expect($activeDatabase)->toBe('huniapp_testing');
});
