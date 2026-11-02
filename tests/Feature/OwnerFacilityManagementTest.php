<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Facility;
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
        ['email' => 'owner_b_fac_test@huniapp.test'],
        [
            'name' => 'Owner Bambang Facilities',
            'phone' => '081299990111',
            'nik' => '3271234505030111',
            'occupation' => 'Pengusaha Kos',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Property for Owner B
    $this->propertyB = Property::firstOrCreate(
        ['slug' => 'kos-bambang-fac'],
        [
            'owner_id' => $this->ownerB->id,
            'name' => 'Kos Bambang Asri',
            'address' => 'Jl. Kaliurang KM 5',
            'city' => 'Sleman',
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

    // Clean up transient test facilities
    Facility::where('name', 'like', 'Test Fac%')->delete();
});

test('1. owner can view facilities catalog index', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.facilities.index'));

    $response->assertOk()
        ->assertSee('Katalog Fasilitas')
        ->assertSee('Semua Kategori', false);
});

test('2. owner can create a new facility in the catalog', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.facilities.store'), [
        'name' => 'Test Fac Kolam Renang',
        'category' => 'umum',
        'icon' => 'sparkles',
        'description' => 'Kolam renang outdoor ukuran 10x5m',
    ]);

    $response->assertRedirect(route('owner.facilities.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('facilities', [
        'name' => 'Test Fac Kolam Renang',
        'category' => 'umum',
    ]);
});

test('3. owner can update an existing facility in catalog', function () {
    $facility = Facility::create([
        'name' => 'Test Fac Gym',
        'category' => 'umum',
        'icon' => 'dumbbell',
        'description' => 'Alat fitness lengkap',
    ]);

    $response = $this->actingAs($this->ownerA)->put(route('owner.facilities.update', $facility), [
        'name' => 'Test Fac Fitness Center',
        'category' => 'umum',
        'icon' => 'dumbbell',
        'description' => 'Fitness center dengan treadmill',
    ]);

    $response->assertRedirect(route('owner.facilities.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('facilities', [
        'id' => $facility->id,
        'name' => 'Test Fac Fitness Center',
    ]);
});

test('4. owner can delete a facility from catalog', function () {
    $facility = Facility::create([
        'name' => 'Test Fac Dapur Umum',
        'category' => 'umum',
        'icon' => 'sparkles',
        'description' => 'Dapur bersama',
    ]);

    $response = $this->actingAs($this->ownerA)->delete(route('owner.facilities.destroy', $facility));

    $response->assertRedirect(route('owner.facilities.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('facilities', [
        'id' => $facility->id,
    ]);
});

test('5. owner can assign facility to their property', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac CCTV 24 Jam'],
        ['category' => 'keamanan', 'icon' => 'lock', 'description' => 'Keamanan terpantau']
    );

    $response = $this->actingAs($this->ownerA)->post(route('owner.properties.facilities.assign', $this->propertyA), [
        'facility_id' => $facility->id,
        'notes' => '16 titik kamera aktif',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('facility_property', [
        'facility_id' => $facility->id,
        'property_id' => $this->propertyA->id,
        'notes' => '16 titik kamera aktif',
    ]);
});

test('6. owner cannot assign facility to another owner property (IDOR prevented)', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac Parkir Luas'],
        ['category' => 'parkir', 'icon' => 'sparkles']
    );

    // Owner A attempts to modify Owner B property
    $response = $this->actingAs($this->ownerA)->post(route('owner.properties.facilities.assign', $this->propertyB), [
        'facility_id' => $facility->id,
        'notes' => 'Hacked',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('facility_property', [
        'facility_id' => $facility->id,
        'property_id' => $this->propertyB->id,
    ]);
});

test('7. owner can detach facility from their property', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac Dapur Bersama'],
        ['category' => 'umum', 'icon' => 'sparkles']
    );

    $this->propertyA->facilities()->syncWithoutDetaching([$facility->id]);

    $response = $this->actingAs($this->ownerA)->delete(route('owner.properties.facilities.detach', [$this->propertyA, $facility]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('facility_property', [
        'facility_id' => $facility->id,
        'property_id' => $this->propertyA->id,
    ]);
});

test('8. owner can assign facility to their room', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac Meja Belajar'],
        ['category' => 'kamar', 'icon' => 'door']
    );

    $response = $this->actingAs($this->ownerA)->post(route('owner.properties.rooms.facilities.assign', [$this->propertyA, $this->roomA3]), [
        'facility_id' => $facility->id,
        'notes' => 'Kayu jati dengan kursi ergonomis',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('facility_room', [
        'facility_id' => $facility->id,
        'room_id' => $this->roomA3->id,
        'notes' => 'Kayu jati dengan kursi ergonomis',
    ]);
});

test('9. room belonging to another property is rejected (404/mismatch)', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac Lemari Baju'],
        ['category' => 'kamar', 'icon' => 'door']
    );

    // Request pairing Property A with Room B1 (which belongs to Property B)
    $response = $this->actingAs($this->ownerA)->post(route('owner.properties.rooms.facilities.assign', [$this->propertyA, $this->roomB1]), [
        'facility_id' => $facility->id,
    ]);

    $response->assertNotFound();
});

test('10. owner can detach facility from their room', function () {
    $facility = Facility::firstOrCreate(
        ['name' => 'Test Fac Kulkas Mini'],
        ['category' => 'kamar', 'icon' => 'door']
    );

    $this->roomA3->facilities()->syncWithoutDetaching([$facility->id]);

    $response = $this->actingAs($this->ownerA)->delete(route('owner.properties.rooms.facilities.detach', [$this->propertyA, $this->roomA3, $facility]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('facility_room', [
        'facility_id' => $facility->id,
        'room_id' => $this->roomA3->id,
    ]);
});

test('11. tenant cannot access facility management endpoints', function () {
    $response = $this->actingAs($this->tenantA)->get(route('owner.facilities.index'));

    $response->assertForbidden();

    $responseStore = $this->actingAs($this->tenantA)->post(route('owner.facilities.store'), [
        'name' => 'Unauthorized Fac',
        'category' => 'umum',
    ]);

    $responseStore->assertForbidden();
});

test('12. guest is redirected to login for facility endpoints', function () {
    $response = $this->get(route('owner.facilities.index'));

    $response->assertRedirect(route('login'));
});

test('13. BUG-02 regression: owner cannot delete facility used by another owners property', function () {
    $sharedFacility = Facility::create([
        'name' => 'Test Fac Generator Listrik A',
        'category' => 'umum',
        'icon' => 'sparkles',
    ]);

    // Attach to Owner A's property
    $this->propertyA->facilities()->attach($sharedFacility->id);

    // Owner B tries to delete the facility
    $response = $this->actingAs($this->ownerB)->delete(route('owner.facilities.destroy', $sharedFacility));

    $response->assertForbidden();

    // Verify facility and Owner A's relationship remain completely intact
    $this->assertDatabaseHas('facilities', ['id' => $sharedFacility->id]);
    $this->assertDatabaseHas('facility_property', [
        'facility_id' => $sharedFacility->id,
        'property_id' => $this->propertyA->id,
    ]);

    // Cleanup
    $this->propertyA->facilities()->detach($sharedFacility->id);
    $sharedFacility->delete();
});

test('14. BUG-02 regression: owner cannot delete facility used by another owners room', function () {
    $sharedRoomFacility = Facility::create([
        'name' => 'Test Fac Smart Lock Kamar A',
        'category' => 'kamar',
        'icon' => 'lock',
    ]);

    // Attach to Owner A's room
    $this->roomA3->facilities()->attach($sharedRoomFacility->id);

    // Owner B tries to delete the facility
    $response = $this->actingAs($this->ownerB)->delete(route('owner.facilities.destroy', $sharedRoomFacility));

    $response->assertForbidden();

    // Verify facility and Owner A's room relationship remain intact
    $this->assertDatabaseHas('facilities', ['id' => $sharedRoomFacility->id]);
    $this->assertDatabaseHas('facility_room', [
        'facility_id' => $sharedRoomFacility->id,
        'room_id' => $this->roomA3->id,
    ]);

    // Cleanup
    $this->roomA3->facilities()->detach($sharedRoomFacility->id);
    $sharedRoomFacility->delete();
});

test('15. BUG-02 regression: owner cannot update facility used by another owners property or room', function () {
    $sharedFacility = Facility::create([
        'name' => 'Test Fac Mesin Cuci Bersama',
        'category' => 'umum',
        'icon' => 'sparkles',
    ]);

    $this->propertyA->facilities()->attach($sharedFacility->id);

    // Owner B tries to update this facility
    $response = $this->actingAs($this->ownerB)->put(route('owner.facilities.update', $sharedFacility), [
        'name' => 'Test Fac Mesin Cuci Dirusak',
        'category' => 'umum',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('facilities', [
        'id' => $sharedFacility->id,
        'name' => 'Test Fac Mesin Cuci Bersama',
    ]);

    // Cleanup
    $this->propertyA->facilities()->detach($sharedFacility->id);
    $sharedFacility->delete();
});

test('16. BUG-02 regression: owner is prevented from deleting facility still used by their own properties or rooms', function () {
    $ownFacility = Facility::create([
        'name' => 'Test Fac Dapur Eksklusif A',
        'category' => 'umum',
        'icon' => 'sparkles',
    ]);

    // Attach to Owner A's property
    $this->propertyA->facilities()->attach($ownFacility->id);

    // Owner A attempts to delete facility while still in use
    $response = $this->actingAs($this->ownerA)->delete(route('owner.facilities.destroy', $ownFacility));

    $response->assertRedirect(route('owner.facilities.index'));
    $response->assertSessionHas('error');

    // Verify facility was NOT deleted
    $this->assertDatabaseHas('facilities', ['id' => $ownFacility->id]);
    $this->assertDatabaseHas('facility_property', [
        'facility_id' => $ownFacility->id,
        'property_id' => $this->propertyA->id,
    ]);

    // Cleanup
    $this->propertyA->facilities()->detach($ownFacility->id);
    $ownFacility->delete();
});
