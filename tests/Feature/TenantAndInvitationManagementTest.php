<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    // 1. Owner A & Property A
    $this->ownerA = User::where('email', 'aksan@huniapp.test')->first();
    $this->propertyA = Property::where('slug', 'kos-harmoni')->first();
    $this->roomA1 = Room::where('property_id', $this->propertyA->id)->where('room_number', 'A1')->first();
    $this->roomA3 = Room::where('property_id', $this->propertyA->id)->where('room_number', 'A3')->first();

    // Tenant A (Budi) who has an active contract in Room A1 of Property A
    $this->tenantA = User::where('email', 'budi@huniapp.test')->first();

    // 2. Owner B & Property B
    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_b_phase4@huniapp.test'],
        [
            'name' => 'Owner Bambang Phase 4',
            'phone' => '081299990099',
            'nik' => '3271234505030999',
            'occupation' => 'Pengusaha Kos',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    $this->propertyB = Property::firstOrCreate(
        ['slug' => 'kos-bambang-phase4'],
        [
            'owner_id' => $this->ownerB->id,
            'name' => 'Kos Bambang Phase 4',
            'type' => 'campur',
            'address' => 'Jl. Bambang No. 8',
            'city' => 'Jakarta Selatan',
            'district' => 'Tebet',
            'postal_code' => '12810',
            'total_rooms' => 5,
            'is_active' => true,
        ]
    );

    $this->roomB1 = Room::firstOrCreate(
        ['property_id' => $this->propertyB->id, 'room_number' => 'B1'],
        [
            'type' => 'deluxe',
            'floor' => 1,
            'price' => 2000000,
            'status' => 'tersedia',
        ]
    );

    // Tenant B who belongs to Owner B
    $this->tenantB = User::firstOrCreate(
        ['email' => 'tenant_b_phase4@huniapp.test'],
        [
            'name' => 'Penyewa Bambang',
            'phone' => '081288887777',
            'nik' => '3271234505030777',
            'occupation' => 'Karyawan',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    // Connect Tenant B to Owner B via an active contract on Room B1
    $this->contractB = Contract::firstOrCreate(
        ['room_id' => $this->roomB1->id, 'user_id' => $this->tenantB->id],
        [
            'property_id' => $this->propertyB->id,
            'contract_number' => 'CTR-B1-PHASE4',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(5),
            'duration_months' => 6,
            'monthly_rent' => 2000000,
            'total_amount' => 12000000,
            'deposit_amount' => 500000,
            'deposit_status' => 'dibayar',
            'payment_method' => 'transfer',
            'status' => 'aktif',
        ]
    );

    // Available room for invitation testing in Property A
    $this->availableRoomA = Room::firstOrCreate(
        ['property_id' => $this->propertyA->id, 'room_number' => 'A99'],
        [
            'type' => 'standar',
            'floor' => 2,
            'price' => 1500000,
            'status' => 'tersedia',
        ]
    );

    // Always ensure available room is 'tersedia'
    $this->availableRoomA->update(['status' => 'tersedia']);

    // Clean up invitations created during test runs
    Invitation::where('email', 'like', '%_phase4%')->delete();
    Invitation::where('room_id', $this->availableRoomA->id)->delete();

    // Clean up temporary test users (except baseline ownerB and tenantB)
    User::where('email', 'like', '%_phase4@huniapp.test')
        ->whereNotIn('email', ['owner_b_phase4@huniapp.test', 'tenant_b_phase4@huniapp.test'])
        ->delete();
});

test('1. owner only sees tenants related to their properties', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.tenants.index'));

    $response->assertOk()
        ->assertSee('Manajemen Penghuni')
        ->assertSee($this->tenantA->name)
        ->assertSee($this->tenantA->email)
        ->assertDontSee($this->tenantB->name);
});

test('2. owner cannot access details of another owners tenant', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.tenants.show', $this->tenantB));

    $response->assertForbidden();
});

test('3. owner cannot create invitations for another owners property or room', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.invitations.store'), [
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'name' => 'Calon Penyewa Test',
        'email' => 'calon_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'valid_days' => 7,
    ]);

    $response->assertSessionHasErrors(['property_id', 'room_id']);
    expect(Invitation::where('email', 'calon_test_phase4@huniapp.test')->exists())->toBeFalse();
});

test('4. invitation is successfully created with a cryptographically secure unique token', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.invitations.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Penyewa Baru',
        'email' => 'calon_baru_test_phase4@huniapp.test',
        'phone' => '081234567899',
        'valid_days' => 7,
    ]);

    $response->assertRedirect();
    $invitation = Invitation::where('email', 'calon_baru_test_phase4@huniapp.test')->first();

    expect($invitation)->not->toBeNull();
    expect(in_array($invitation->status, ['aktif', 'pending'], true))->toBeTrue();
    expect($invitation->invited_by)->toBe($this->ownerA->id);
    expect($invitation->room_id)->toBe($this->availableRoomA->id);
    expect(strlen($invitation->token))->toBe(40);
    expect($invitation->valid_until->isFuture())->toBeTrue();
});

test('5. duplicate pending invitation for same room and email is rejected', function () {
    // Create initial pending invitation
    Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Duplikat',
        'email' => 'duplikat_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    // Attempt to create another pending invitation for the exact same room & email
    $response = $this->actingAs($this->ownerA)->post(route('owner.invitations.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Duplikat Kedua',
        'email' => 'duplikat_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'valid_days' => 7,
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('6. expired invitation cannot be accepted', function () {
    $expiredInvitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Expired',
        'email' => 'expired_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->subDay(), // Expired
    ]);

    $tenant = User::firstOrCreate(
        ['email' => 'expired_test_phase4@huniapp.test'],
        [
            'name' => 'Calon Expired User',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($tenant)->post(route('invitations.accept', $expiredInvitation->token));

    $response->assertSessionHas('error');
    $expiredInvitation->refresh();
    expect(in_array($expiredInvitation->status, ['aktif', 'pending'], true))->toBeTrue();
    expect($expiredInvitation->user_id)->toBeNull();
});

test('7. invalid invitation token returns 404', function () {
    $response = $this->get(route('invitations.show', 'token-yang-pasti-tidak-ada-123456'));

    $response->assertNotFound();
});

test('8. already accepted invitation cannot be reused', function () {
    $acceptedInvitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Accepted',
        'email' => 'already_accepted_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'diterima',
        'accepted_at' => now()->subHour(),
        'user_id' => $this->tenantA->id,
        'valid_until' => now()->addDays(7),
    ]);

    $tenant = User::firstOrCreate(
        ['email' => 'already_accepted_test_phase4@huniapp.test'],
        [
            'name' => 'Calon Accepted User',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($tenant)->post(route('invitations.accept', $acceptedInvitation->token));

    $response->assertSessionHas('error');
});

test('9. user email must match invitation email to accept', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Khusus',
        'email' => 'khusus_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    // Tenant with a DIFFERENT email attempts to accept
    $impostor = User::firstOrCreate(
        ['email' => 'impostor_tenant_phase4@huniapp.test'],
        [
            'name' => 'Tenant Lain',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($impostor)->post(route('invitations.accept', $invitation->token));

    $response->assertSessionHas('error');
    $invitation->refresh();
    expect(in_array($invitation->status, ['aktif', 'pending'], true))->toBeTrue();
    expect($invitation->user_id)->toBeNull();
});

test('10. revoked invitation cannot be accepted', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Batal',
        'email' => 'batal_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'dibatalkan',
        'valid_until' => now()->addDays(7),
    ]);

    $tenant = User::firstOrCreate(
        ['email' => 'batal_test_phase4@huniapp.test'],
        [
            'name' => 'Penyewa Batal',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($tenant)->post(route('invitations.accept', $invitation->token));

    $response->assertSessionHas('error');
    $invitation->refresh();
    expect($invitation->status)->toBe('dibatalkan');
    expect($invitation->user_id)->toBeNull();
});

test('11. accepting invitation correctly links tenant user, updates invitation status, and marks room as occupied', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Penyewa Sukses',
        'email' => 'sukses_test_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    $tenant = User::firstOrCreate(
        ['email' => 'sukses_test_phase4@huniapp.test'],
        [
            'name' => 'Penyewa Sukses User',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($tenant)->post(route('invitations.accept', $invitation->token));

    $response->assertRedirect(route('tenant.dashboard'));

    $invitation->refresh();
    expect($invitation->status)->toBe('diterima');
    expect($invitation->accepted_at)->not->toBeNull();
    expect($invitation->user_id)->toBe($tenant->id);

    $this->availableRoomA->refresh();
    expect($this->availableRoomA->status)->toBe('terisi');
});

test('12. unauthenticated user cannot access owner tenant and invitation management routes', function () {
    $this->get(route('owner.tenants.index'))->assertRedirect(route('login'));
    $this->get(route('owner.invitations.index'))->assertRedirect(route('login'));
    $this->get(route('owner.invitations.create'))->assertRedirect(route('login'));
});

test('13. owner cannot access or revoke another owners invitations', function () {
    // Invitation owned by Owner A
    $invitationA = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Milik Owner A',
        'email' => 'owner_a_priv_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    // Owner B tries to access detail of Owner A's invitation
    $this->actingAs($this->ownerB)->get(route('owner.invitations.show', $invitationA))
        ->assertForbidden();

    // Owner B tries to revoke Owner A's invitation
    $this->actingAs($this->ownerB)->post(route('owner.invitations.revoke', $invitationA))
        ->assertForbidden();

    // Verify invitation status remains unchanged
    $invitationA->refresh();
    expect(in_array($invitationA->status, ['aktif', 'pending'], true))->toBeTrue();
});

test('14. guest can register a new tenant account and accept invitation simultaneously', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Guest Baru',
        'email' => 'guest_register_phase4@huniapp.test',
        'phone' => '081211112222',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    $response = $this->post(route('invitations.register', $invitation->token), [
        'name' => 'Penyewa Guest Baru',
        'phone' => '081211112222',
        'nik' => '3271234505030111',
        'occupation' => 'Desainer Grafis',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('tenant.dashboard'));

    $newUser = User::where('email', 'guest_register_phase4@huniapp.test')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->role)->toBe(UserRole::Tenant);
    expect($newUser->name)->toBe('Penyewa Guest Baru');

    $invitation->refresh();
    expect($invitation->status)->toBe('diterima');
    expect($invitation->user_id)->toBe($newUser->id);

    $this->availableRoomA->refresh();
    expect($this->availableRoomA->status)->toBe('terisi');
});

test('15. invitation cannot be accepted if room is already marked terisi', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Kamar Penuh',
        'email' => 'kamar_penuh_phase4@huniapp.test',
        'phone' => '081234567890',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    // Mark room as occupied
    $this->availableRoomA->update(['status' => 'terisi']);

    $tenant = User::firstOrCreate(
        ['email' => 'kamar_penuh_phase4@huniapp.test'],
        [
            'name' => 'Penyewa Kamar Penuh User',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $response = $this->actingAs($tenant)->post(route('invitations.accept', $invitation->token));

    $response->assertSessionHas('error', 'Maaf, kamar ini sedang ditempati dan tidak dapat disewa saat ini.');
    $invitation->refresh();
    expect($invitation->status)->toBe('aktif');
    expect($invitation->user_id)->toBeNull();
});

test('16. guest cannot register and accept if room is already marked terisi or has active contract', function () {
    $invitation = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->availableRoomA->id,
        'name' => 'Calon Guest Kamar Penuh',
        'email' => 'guest_penuh_phase4@huniapp.test',
        'phone' => '081211113333',
        'token' => Str::random(40),
        'status' => 'aktif',
        'valid_until' => now()->addDays(7),
    ]);

    // Mark room as occupied
    $this->availableRoomA->update(['status' => 'terisi']);

    $response = $this->post(route('invitations.register', $invitation->token), [
        'name' => 'Penyewa Guest Penuh',
        'phone' => '081211113333',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHas('error', 'Maaf, kamar ini sedang ditempati dan tidak dapat disewa saat ini.');
    $invitation->refresh();
    expect($invitation->status)->toBe('aktif');
    expect($invitation->user_id)->toBeNull();
    expect(User::where('email', 'guest_penuh_phase4@huniapp.test')->exists())->toBeFalse();
});
