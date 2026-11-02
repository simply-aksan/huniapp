<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Primary Owner A & Tenant A from Seeder
    $this->ownerA = User::where('email', 'aksan@huniapp.test')->first();
    $this->tenantA = User::where('email', 'budi@huniapp.test')->first();
    $this->propertyA = Property::where('slug', 'kos-harmoni')->first();
    $this->roomA3 = Room::where('property_id', $this->propertyA->id)->where('room_number', 'A3')->first();

    // Isolated Second Owner B
    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_b_test@huniapp.test'],
        [
            'name' => 'Owner Bambang',
            'phone' => '081299990099',
            'nik' => '3271234505030999',
            'occupation' => 'Pengusaha Properti',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Empty Owner C (no properties, no rooms)
    $this->ownerC = User::firstOrCreate(
        ['email' => 'owner_c_empty@huniapp.test'],
        [
            'name' => 'Owner Cantika',
            'phone' => '081299990088',
            'nik' => '3271234505030888',
            'occupation' => 'Pemilik Kos Baru',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Clean up any transient test records from previous runs
    Room::where('room_number', 'C99')->delete();
    Property::where('name', 'like', 'Kos Harmoni Putri 2%')->delete();
    Property::where('slug', 'kos-bambang-asri')->delete();
    Property::where('slug', 'kos-harmoni')->update(['is_primary' => true]);
    Property::where('slug', '!=', 'kos-harmoni')->where('owner_id', $this->ownerA->id)->update(['is_primary' => false]);
});

test('1. owner can view dashboard with their own live database data', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.dashboard'));

    $response->assertOk()
        ->assertSee('Selamat datang')
        ->assertSee($this->ownerA->name)
        ->assertSee('Kos Harmoni')
        ->assertSee('Total Kamar')
        ->assertSee('Status Kamar')
        ->assertSee('Tagihan & Invoice Terbaru', false);
});

test('2. dashboard does not display data belonging to another owner', function () {
    // Owner B creates their own property
    $propertyB = Property::firstOrCreate(
        ['slug' => 'kos-bambang-asri'],
        [
            'owner_id' => $this->ownerB->id,
            'name' => 'Kos Bambang Asri',
            'address' => 'Jl. Kaliurang Km 5',
            'city' => 'Sleman',
            'status' => 'aktif',
            'is_primary' => true,
        ]
    );

    $response = $this->actingAs($this->ownerB)->get(route('owner.dashboard'));

    $response->assertOk()
        ->assertSee('Kos Bambang Asri')
        ->assertDontSee('Kos Harmoni');
});

test('3. owner can create, view, and edit their own property', function () {
    // View create form
    $this->actingAs($this->ownerA)
        ->get(route('owner.properties.create'))
        ->assertOk()
        ->assertSee('Tambah Properti Baru');

    // Store new property
    $storeResponse = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.store'), [
            'name' => 'Kos Harmoni Putri 2',
            'address' => 'Jl. Pandega Marta No. 8',
            'city' => 'Sleman',
            'district' => 'Mlati',
            'postal_code' => '55281',
            'description' => 'Kos khusus mahasiswi aman dan tenang.',
            'status' => 'aktif',
            'is_primary' => '0',
        ]);

    $property = Property::where('name', 'Kos Harmoni Putri 2')->first();
    expect($property)->not->toBeNull()
        ->and($property->owner_id)->toBe($this->ownerA->id);

    $storeResponse->assertRedirect(route('owner.properties.show', $property));

    // View property details
    $this->actingAs($this->ownerA)
        ->get(route('owner.properties.show', $property))
        ->assertOk()
        ->assertSee('Kos Harmoni Putri 2')
        ->assertSee('Jl. Pandega Marta No. 8');

    // View edit form
    $this->actingAs($this->ownerA)
        ->get(route('owner.properties.edit', $property))
        ->assertOk()
        ->assertSee('Edit Properti: Kos Harmoni Putri 2');

    // Update property
    $updateResponse = $this->actingAs($this->ownerA)
        ->put(route('owner.properties.update', $property), [
            'name' => 'Kos Harmoni Putri 2 Updated',
            'address' => 'Jl. Pandega Marta No. 8B',
            'city' => 'Sleman',
            'district' => 'Mlati',
            'postal_code' => '55281',
            'description' => 'Deskripsi diperbarui.',
            'status' => 'aktif',
            'is_primary' => '0',
        ]);

    $updateResponse->assertRedirect(route('owner.properties.show', $property));
    expect($property->fresh()->name)->toBe('Kos Harmoni Putri 2 Updated');
});

test('4. owner cannot view, edit, update, or delete another owner\'s property', function () {
    // Owner B attempts to view Owner A's property
    $this->actingAs($this->ownerB)
        ->get(route('owner.properties.show', $this->propertyA))
        ->assertForbidden();

    // Owner B attempts to view edit form
    $this->actingAs($this->ownerB)
        ->get(route('owner.properties.edit', $this->propertyA))
        ->assertForbidden();

    // Owner B attempts to update
    $this->actingAs($this->ownerB)
        ->put(route('owner.properties.update', $this->propertyA), [
            'name' => 'Hacked Name',
            'address' => 'Jl. Hacked',
            'city' => 'Jakarta',
            'status' => 'aktif',
        ])
        ->assertForbidden();

    // Owner B attempts to delete
    $this->actingAs($this->ownerB)
        ->delete(route('owner.properties.destroy', $this->propertyA))
        ->assertForbidden();
});

test('5. owner can manage rooms only on their own properties', function () {
    // Owner A can view room create form for property A
    $this->actingAs($this->ownerA)
        ->get(route('owner.properties.rooms.create', $this->propertyA))
        ->assertOk()
        ->assertSee('Tambah Kamar Baru');

    // Owner A can create room on property A
    $roomResponse = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.rooms.store', $this->propertyA), [
            'room_number' => 'C99',
            'floor' => 2,
            'type' => 'Deluxe',
            'price' => 2100000,
            'size' => '4 x 4 m',
            'capacity' => 1,
            'status' => 'kosong',
            'description' => 'Kamar sudut dengan pemandangan luas.',
        ]);

    $room = Room::where('property_id', $this->propertyA->id)->where('room_number', 'C99')->first();
    expect($room)->not->toBeNull();
    $roomResponse->assertRedirect(route('owner.properties.rooms.show', [$this->propertyA, $room]));

    // Owner B cannot create room on Owner A's property
    $this->actingAs($this->ownerB)
        ->post(route('owner.properties.rooms.store', $this->propertyA), [
            'room_number' => 'HACK1',
            'floor' => 1,
            'type' => 'Standard',
            'price' => 1000000,
            'capacity' => 1,
            'status' => 'kosong',
        ])
        ->assertForbidden();

    // Owner B cannot view Owner A's room
    $this->actingAs($this->ownerB)
        ->get(route('owner.properties.rooms.show', [$this->propertyA, $this->roomA3]))
        ->assertForbidden();

    // Owner B cannot edit Owner A's room
    $this->actingAs($this->ownerB)
        ->get(route('owner.properties.rooms.edit', [$this->propertyA, $this->roomA3]))
        ->assertForbidden();
});

test('6. duplicate room number within the same property is rejected', function () {
    // Room A3 already exists in property A
    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.rooms.store', $this->propertyA), [
            'room_number' => 'A3', // duplicate!
            'floor' => 1,
            'type' => 'Deluxe',
            'price' => 2000000,
            'capacity' => 1,
            'status' => 'kosong',
        ]);

    $response->assertSessionHasErrors('room_number');
});

test('7. form validation and active contract room status protection work correctly', function () {
    // Room A3 has an active rental contract for tenant Budi
    expect($this->roomA3->contracts()->where('status', 'aktif')->exists())->toBeTrue();

    // Attempting to set room status to 'kosong' while contract is active must fail
    $response = $this->actingAs($this->ownerA)
        ->put(route('owner.properties.rooms.update', [$this->propertyA, $this->roomA3]), [
            'room_number' => 'A3',
            'floor' => 1,
            'type' => 'Deluxe',
            'price' => 2000000,
            'capacity' => 1,
            'status' => 'kosong', // Forbidden because active contract exists!
        ]);

    $response->assertSessionHasErrors('status');
});

test('8. deletion does not destroy linked contract or financial data', function () {
    // Attempting to delete property A which has contracts and bills
    $response = $this->actingAs($this->ownerA)
        ->delete(route('owner.properties.destroy', $this->propertyA));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($this->propertyA->fresh())->not->toBeNull();

    // Attempting to delete room A3 which has contract
    $roomDeleteResponse = $this->actingAs($this->ownerA)
        ->delete(route('owner.properties.rooms.destroy', [$this->propertyA, $this->roomA3]));

    $roomDeleteResponse->assertRedirect();
    $roomDeleteResponse->assertSessionHas('error');
    expect($this->roomA3->fresh())->not->toBeNull();
});

test('9. empty state renders gracefully when owner has no data', function () {
    // Owner C has no properties
    $response = $this->actingAs($this->ownerC)->get(route('owner.dashboard'));

    $response->assertOk()
        ->assertSee('Selamat datang')
        ->assertSee($this->ownerC->name)
        ->assertSee('Total Kamar')
        ->assertSee('0')
        ->assertSee('Belum Ada Tagihan Terbit')
        ->assertSee('Tidak Ada Keluhan');

    $propsResponse = $this->actingAs($this->ownerC)->get(route('owner.properties.index'));

    $propsResponse->assertOk()
        ->assertSee('Belum Ada Properti Terdaftar')
        ->assertSee('Tambah Properti Sekarang');
});

test('10. navigation and sidebar link to property management without regression', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.properties.index'));

    $response->assertOk()
        ->assertSee('Properti &amp; Kos', false)
        ->assertSee('Kos Harmoni')
        ->assertSee(route('owner.properties.create'))
        ->assertSee(route('owner.properties.show', $this->propertyA));
});
