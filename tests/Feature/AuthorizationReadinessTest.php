<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Primary Owner & Tenant from Seeder
    $this->ownerA = User::where('email', 'aksan@huniapp.test')->first();
    $this->tenantA = User::where('email', 'budi@huniapp.test')->first();
    $this->propertyA = Property::where('slug', 'kos-harmoni')->first();
    $this->roomA3 = Room::where('room_number', 'A3')->first();
    $this->contractA = Contract::where('contract_number', 'KT-2025-001')->first();
    $this->billA = Bill::where('bill_number', 'INV-2025-032')->first();
    $this->complaintA = Complaint::where('ticket_number', 'KMP-2026-0004')->first();
    $this->invitationA = Invitation::where('token', 'inv-token-demo-123456')->first();

    // Isolated Second Owner B
    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_b@huniapp.test'],
        [
            'name' => 'Owner Budiarto',
            'phone' => '081299990002',
            'nik' => '3271234505030099',
            'occupation' => 'Juragan Kos',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Isolated Second Tenant B
    $this->tenantB = User::firstOrCreate(
        ['email' => 'tenant_b@huniapp.test'],
        [
            'name' => 'Citra Lestari',
            'phone' => '081299990003',
            'nik' => '3271234505030098',
            'occupation' => 'Karyawan Swasta',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );
});

test('property policy allows owning owner but denies other owner and tenants', function () {
    // Owner A owns propertyA
    expect(Gate::forUser($this->ownerA)->allows('view', $this->propertyA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('update', $this->propertyA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('delete', $this->propertyA))->toBeTrue();

    // Owner B must NOT have access to Owner A's property
    expect(Gate::forUser($this->ownerB)->allows('view', $this->propertyA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('update', $this->propertyA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('delete', $this->propertyA))->toBeFalse();

    // Tenants must NOT manage or own properties
    expect(Gate::forUser($this->tenantA)->allows('view', $this->propertyA))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('update', $this->propertyA))->toBeFalse()
        ->and(Gate::forUser($this->tenantB)->allows('view', $this->propertyA))->toBeFalse();
});

test('contract policy allows owning owner and assigned tenant but denies third parties', function () {
    // Owner A owns the property where contract exists
    expect(Gate::forUser($this->ownerA)->allows('view', $this->contractA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('update', $this->contractA))->toBeTrue();

    // Tenant A is the contract party and can view, but cannot mutate
    expect(Gate::forUser($this->tenantA)->allows('view', $this->contractA))->toBeTrue()
        ->and(Gate::forUser($this->tenantA)->allows('update', $this->contractA))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('delete', $this->contractA))->toBeFalse();

    // Owner B cannot view or modify Owner A's tenant contract
    expect(Gate::forUser($this->ownerB)->allows('view', $this->contractA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('update', $this->contractA))->toBeFalse();

    // Tenant B cannot view Tenant A's contract
    expect(Gate::forUser($this->tenantB)->allows('view', $this->contractA))->toBeFalse()
        ->and(Gate::forUser($this->tenantB)->allows('update', $this->contractA))->toBeFalse();
});

test('bill policy allows owning owner and billing recipient but denies other users', function () {
    // Owner A can view and manage bills for their property
    expect(Gate::forUser($this->ownerA)->allows('view', $this->billA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('update', $this->billA))->toBeTrue();

    // Tenant A can view their own bill, but cannot mutate bill details
    expect(Gate::forUser($this->tenantA)->allows('view', $this->billA))->toBeTrue()
        ->and(Gate::forUser($this->tenantA)->allows('update', $this->billA))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('delete', $this->billA))->toBeFalse();

    // Owner B cannot view or update Owner A's bill
    expect(Gate::forUser($this->ownerB)->allows('view', $this->billA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('update', $this->billA))->toBeFalse();

    // Tenant B cannot view Tenant A's bill
    expect(Gate::forUser($this->tenantB)->allows('view', $this->billA))->toBeFalse()
        ->and(Gate::forUser($this->tenantB)->allows('update', $this->billA))->toBeFalse();
});

test('complaint policy isolates tenant tickets between owners and other tenants', function () {
    // Owner A can view and respond to complaints filed for their property
    expect(Gate::forUser($this->ownerA)->allows('view', $this->complaintA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('update', $this->complaintA))->toBeTrue();

    // Tenant A can view and update their own complaint
    expect(Gate::forUser($this->tenantA)->allows('view', $this->complaintA))->toBeTrue()
        ->and(Gate::forUser($this->tenantA)->allows('update', $this->complaintA))->toBeTrue();

    // Owner B cannot view or resolve Owner A's tenant complaint
    expect(Gate::forUser($this->ownerB)->allows('view', $this->complaintA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('update', $this->complaintA))->toBeFalse();

    // Tenant B cannot view or tamper with Tenant A's complaint
    expect(Gate::forUser($this->tenantB)->allows('view', $this->complaintA))->toBeFalse()
        ->and(Gate::forUser($this->tenantB)->allows('update', $this->complaintA))->toBeFalse();
});

test('invitation ownership ensures invitations belong strictly to inviting owner', function () {
    expect($this->invitationA->invited_by)->toBe($this->ownerA->id)
        ->and($this->invitationA->sender->id)->toBe($this->ownerA->id);

    // Owner A sentInvitations includes invitationA
    expect($this->ownerA->sentInvitations->pluck('id'))->toContain($this->invitationA->id);

    // Owner B sentInvitations does NOT include invitationA
    expect($this->ownerB->sentInvitations->pluck('id'))->not->toContain($this->invitationA->id);
});

test('relationship traversal validates owner hierarchy for rooms and inventory', function () {
    // Rooms and Inventory trace upward to Property Owner
    expect($this->roomA3->property->isOwnedBy($this->ownerA))->toBeTrue()
        ->and($this->roomA3->property->isOwnedBy($this->ownerB))->toBeFalse();

    $inventory = $this->propertyA->inventories()->first();
    expect($inventory)->not->toBeNull()
        ->and($inventory->property->isOwnedBy($this->ownerA))->toBeTrue()
        ->and($inventory->property->isOwnedBy($this->ownerB))->toBeFalse();
});
