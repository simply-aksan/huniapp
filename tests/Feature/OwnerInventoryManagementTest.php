<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->ownerA = User::where('email', 'aksan@huniapp.test')->first();
    $this->tenantA = User::where('email', 'budi@huniapp.test')->first();
    $this->propertyA = Property::where('slug', 'kos-harmoni')->first();
    $this->roomA3 = Room::where('property_id', $this->propertyA->id)->where('room_number', 'A3')->first();

    // Isolated Second Owner B
    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_b_inv_test@huniapp.test'],
        [
            'name' => 'Owner Bambang Inventory',
            'phone' => '081299990222',
            'nik' => '3271234505030222',
            'occupation' => 'Pengusaha Kos',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Property for Owner B
    $this->propertyB = Property::firstOrCreate(
        ['slug' => 'kos-bambang-inv'],
        [
            'owner_id' => $this->ownerB->id,
            'name' => 'Kos Bambang Asri 2',
            'address' => 'Jl. Gejayan No. 8',
            'city' => 'Yogyakarta',
            'status' => 'aktif',
            'is_primary' => true,
        ]
    );

    $this->roomB1 = Room::firstOrCreate(
        ['property_id' => $this->propertyB->id, 'room_number' => 'B-01'],
        [
            'floor' => 1,
            'type' => 'Standard',
            'price' => 1200000,
            'capacity' => 1,
            'status' => 'kosong',
        ]
    );

    // Clean up transient test inventory
    Inventory::where('name', 'like', 'Test Inv%')->delete();
});

test('1. owner can view inventory index with aggregate stats', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.inventory.index'));

    $response->assertOk()
        ->assertSee('Inventaris Kos')
        ->assertSee('Total Inventaris')
        ->assertSee('Kondisi Baik')
        ->assertSee('Perlu Perawatan')
        ->assertSee('Rusak / Hilang', false);
});

test('2. owner can view create inventory form with properties and rooms', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.inventory.create'));

    $response->assertOk()
        ->assertSee('Tambah Barang Inventaris')
        ->assertSee($this->propertyA->name);
});

test('3. owner can store new inventory item with valid data', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.inventory.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA3->id,
        'name' => 'Test Inv Kulkas Sharp 1 Pintu',
        'serial_number' => 'SN-SHP-991',
        'category' => 'Elektronik',
        'brand' => 'Sharp',
        'model' => 'SJ-N162D',
        'location' => 'Sudut Kamar A3',
        'quantity' => 1,
        'condition' => 'baik',
        'purchase_date' => '2026-01-15',
        'purchase_price' => 1800000,
        'warranty_until' => '2027-01-15',
        'last_checked_at' => '2026-10-01',
        'notes' => 'Berfungsi normal dan hemat listrik',
    ]);

    $response->assertRedirect(route('owner.inventory.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('inventory', [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA3->id,
        'name' => 'Test Inv Kulkas Sharp 1 Pintu',
        'serial_number' => 'SN-SHP-991',
        'condition' => 'baik',
    ]);
});

test('4. validation fails when required attributes are missing or quantity is zero', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.inventory.store'), [
        'property_id' => $this->propertyA->id,
        'name' => '',
        'category' => '',
        'quantity' => 0,
        'condition' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['name', 'category', 'quantity', 'condition']);
});

test('5. owner cannot assign inventory to another owner property (IDOR prevented)', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.inventory.store'), [
        'property_id' => $this->propertyB->id, // Belongs to Owner B!
        'name' => 'Test Inv Hacked TV',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $response->assertSessionHasErrors(['property_id']);

    $this->assertDatabaseMissing('inventory', [
        'name' => 'Test Inv Hacked TV',
    ]);
});

test('6. owner cannot assign inventory to a room of a different property', function () {
    // Property A selected, but Room B1 belongs to Property B!
    $response = $this->actingAs($this->ownerA)->post(route('owner.inventory.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomB1->id,
        'name' => 'Test Inv Mismatched Chair',
        'category' => 'Perabot',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $response->assertSessionHasErrors(['room_id']);

    $this->assertDatabaseMissing('inventory', [
        'name' => 'Test Inv Mismatched Chair',
    ]);
});

test('7. owner can view inventory item detail both html and json', function () {
    $item = Inventory::create([
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA3->id,
        'name' => 'Test Inv Meja Belajar Kayu',
        'category' => 'Perabot',
        'quantity' => 1,
        'condition' => 'baik',
        'purchase_price' => 500000,
    ]);

    // HTML response
    $responseHtml = $this->actingAs($this->ownerA)->get(route('owner.inventory.show', $item));
    $responseHtml->assertOk()
        ->assertSee('Test Inv Meja Belajar Kayu')
        ->assertSee($this->propertyA->name);

    // JSON response for interactive slide-over
    $responseJson = $this->actingAs($this->ownerA)->getJson(route('owner.inventory.show', $item));
    $responseJson->assertOk()
        ->assertJson([
            'id' => $item->id,
            'name' => 'Test Inv Meja Belajar Kayu',
            'category' => 'Perabot',
        ]);
});

test('8. owner cannot view another owner inventory item (403 forbidden)', function () {
    $itemB = Inventory::create([
        'property_id' => $this->propertyB->id,
        'name' => 'Test Inv Private Safe B',
        'category' => 'Keamanan',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    // Owner A attempts to view item belonging to Owner B
    $response = $this->actingAs($this->ownerA)->get(route('owner.inventory.show', $itemB));
    $response->assertForbidden();

    $responseJson = $this->actingAs($this->ownerA)->getJson(route('owner.inventory.show', $itemB));
    $responseJson->assertForbidden();
});

test('9. owner can edit and update their inventory item', function () {
    $item = Inventory::create([
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Lampu Meja',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $responseEdit = $this->actingAs($this->ownerA)->get(route('owner.inventory.edit', $item));
    $responseEdit->assertOk()->assertSee('Test Inv Lampu Meja');

    $responseUpdate = $this->actingAs($this->ownerA)->put(route('owner.inventory.update', $item), [
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Lampu Meja LED Smart',
        'category' => 'Elektronik',
        'quantity' => 2,
        'condition' => 'perlu_perawatan',
        'notes' => 'Ganti adaptor kabel',
    ]);

    $responseUpdate->assertRedirect(route('owner.inventory.index'));
    $responseUpdate->assertSessionHas('success');

    $this->assertDatabaseHas('inventory', [
        'id' => $item->id,
        'name' => 'Test Inv Lampu Meja LED Smart',
        'quantity' => 2,
        'condition' => 'perlu_perawatan',
        'notes' => 'Ganti adaptor kabel',
    ]);
});

test('10. owner cannot update another owner inventory item (403 forbidden)', function () {
    $itemB = Inventory::create([
        'property_id' => $this->propertyB->id,
        'name' => 'Test Inv AC Bambang',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $response = $this->actingAs($this->ownerA)->put(route('owner.inventory.update', $itemB), [
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Stolen AC',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('inventory', [
        'id' => $itemB->id,
        'name' => 'Test Inv AC Bambang',
    ]);
});

test('11. owner can delete their inventory item', function () {
    $item = Inventory::create([
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Kursi Rusak',
        'category' => 'Perabot',
        'quantity' => 1,
        'condition' => 'rusak',
    ]);

    $response = $this->actingAs($this->ownerA)->delete(route('owner.inventory.destroy', $item));

    $response->assertRedirect(route('owner.inventory.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('inventory', [
        'id' => $item->id,
    ]);
});

test('12. owner cannot delete another owner inventory item (403 forbidden)', function () {
    $itemB = Inventory::create([
        'property_id' => $this->propertyB->id,
        'name' => 'Test Inv Router Bambang',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $response = $this->actingAs($this->ownerA)->delete(route('owner.inventory.destroy', $itemB));

    $response->assertForbidden();

    $this->assertDatabaseHas('inventory', [
        'id' => $itemB->id,
    ]);
});

test('13. filtering inventory by condition works properly', function () {
    Inventory::create([
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Kipas Rusak',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'rusak',
    ]);

    $responseBaik = $this->actingAs($this->ownerA)->get(route('owner.inventory.index', ['condition' => 'baik']));
    $responseBaik->assertOk()->assertDontSee('Test Inv Kipas Rusak');

    $responseRusak = $this->actingAs($this->ownerA)->get(route('owner.inventory.index', ['condition' => 'rusak']));
    $responseRusak->assertOk()->assertSee('Test Inv Kipas Rusak');
});

test('14. searching inventory by keyword matches item attributes', function () {
    Inventory::create([
        'property_id' => $this->propertyA->id,
        'name' => 'Test Inv Water Dispenser',
        'brand' => 'Miyako UniqueBrand',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);

    $responseFound = $this->actingAs($this->ownerA)->get(route('owner.inventory.index', ['search' => 'UniqueBrand']));
    $responseFound->assertOk()->assertSee('Test Inv Water Dispenser');

    $responseNotFound = $this->actingAs($this->ownerA)->get(route('owner.inventory.index', ['search' => 'NonExistentXYZ99']));
    $responseNotFound->assertOk()->assertDontSee('Test Inv Water Dispenser');
});

test('15. tenant cannot access inventory management endpoints', function () {
    $responseIndex = $this->actingAs($this->tenantA)->get(route('owner.inventory.index'));
    $responseIndex->assertForbidden();

    $responseCreate = $this->actingAs($this->tenantA)->get(route('owner.inventory.create'));
    $responseCreate->assertForbidden();

    $responseStore = $this->actingAs($this->tenantA)->post(route('owner.inventory.store'), [
        'property_id' => $this->propertyA->id,
        'name' => 'Tenant Item',
        'category' => 'Elektronik',
        'quantity' => 1,
        'condition' => 'baik',
    ]);
    $responseStore->assertForbidden();
});

test('16. guest is redirected to login for inventory endpoints', function () {
    $response = $this->get(route('owner.inventory.index'));

    $response->assertRedirect(route('login'));
});
