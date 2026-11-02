<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomOccupancyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // 1. Owner A & Property A (Kos Harmoni)
    $this->ownerA = User::where('email', 'aksan@huniapp.test')->first();
    $this->propertyA = Property::where('slug', 'kos-harmoni')->first();

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
            'status' => 'terisi',
        ]
    );

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

    // 3. Dedicated test room in Property A
    $this->roomTest = Room::firstOrCreate(
        ['property_id' => $this->propertyA->id, 'room_number' => 'A98'],
        [
            'type' => 'deluxe',
            'floor' => 2,
            'price' => 1750000,
            'status' => 'kosong',
        ]
    );
    $this->roomTest->update(['status' => 'kosong']);

    // 4. Dedicated test tenant in Property A with accepted invitation
    $this->tenantTest = User::firstOrCreate(
        ['email' => 'tenant_phase5@huniapp.test'],
        [
            'name' => 'Penyewa Uji Phase 5',
            'phone' => '081233445566',
            'nik' => '3271234505030555',
            'occupation' => 'Mahasiswa',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    $this->invitationTest = Invitation::firstOrCreate(
        ['room_id' => $this->roomTest->id, 'email' => 'tenant_phase5@huniapp.test'],
        [
            'invited_by' => $this->ownerA->id,
            'user_id' => $this->tenantTest->id,
            'name' => 'Penyewa Uji Phase 5',
            'phone' => '081233445566',
            'token' => 'token_phase5_'.bin2hex(random_bytes(8)),
            'status' => 'diterima',
            'valid_until' => now()->addDays(7),
            'accepted_at' => now(),
        ]
    );
    $this->invitationTest->update(['status' => 'diterima', 'user_id' => $this->tenantTest->id]);

    // 5. Unrelated tenant (has no invitation or contract for roomTest)
    $this->unrelatedTenant = User::firstOrCreate(
        ['email' => 'unrelated_tenant_phase5@huniapp.test'],
        [
            'name' => 'Penyewa Tidak Terkait',
            'phone' => '081211223344',
            'nik' => '3271234505030444',
            'occupation' => 'Wiraswasta',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    // Clean up temporary contracts created in tests for this room and test tenant
    ContractDocument::whereHas('contract', fn ($q) => $q->where('room_id', $this->roomTest->id)->orWhere('user_id', $this->tenantTest->id))->delete();
    BillItem::whereHas('bill', fn ($q) => $q->where('user_id', $this->tenantTest->id))->delete();
    Bill::where('user_id', $this->tenantTest->id)->delete();
    Contract::where('room_id', $this->roomTest->id)->orWhere('user_id', $this->tenantTest->id)->delete();
});

afterEach(function () {
    // Clean up test data safely without affecting baseline seed data
    ContractDocument::whereHas('contract', fn ($q) => $q->where('room_id', $this->roomTest->id)->orWhere('user_id', $this->tenantTest->id))->delete();
    BillItem::whereHas('bill', fn ($q) => $q->where('user_id', $this->tenantTest->id))->delete();
    Bill::where('user_id', $this->tenantTest->id)->delete();
    Contract::where('room_id', $this->roomTest->id)->orWhere('user_id', $this->tenantTest->id)->delete();
    Invitation::where('email', 'tenant_phase5@huniapp.test')->delete();
    User::whereIn('email', ['tenant_phase5@huniapp.test', 'unrelated_tenant_phase5@huniapp.test'])->delete();
    Room::where('room_number', 'A98')->delete();
});

test('1. owner can view their own contracts', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-001',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 500000,
        'deposit_status' => 'belum_dibayar',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->ownerA)->get(route('owner.contracts.index'));

    $response->assertOk()
        ->assertViewIs('owner.contracts.index')
        ->assertSee('CTR-TEST-PHASE5-001')
        ->assertSee($this->tenantTest->name);
});

test("2. owner cannot access other owner's contract", function () {
    $contractB = Contract::firstOrCreate(
        ['contract_number' => 'CTR-TEST-PHASE5-B01'],
        [
            'property_id' => $this->propertyB->id,
            'room_id' => $this->roomB1->id,
            'user_id' => $this->tenantB->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'duration_months' => 3,
            'monthly_rent' => 2000000,
            'total_amount' => 6000000,
            'deposit_amount' => 0,
            'deposit_status' => 'lunas',
            'payment_method' => 'transfer_bank',
            'status' => 'aktif',
        ]
    );

    // Owner A tries to view Owner B's contract
    $response = $this->actingAs($this->ownerA)->get(route('owner.contracts.show', $contractB));
    $response->assertForbidden();

    // Owner A tries to edit Owner B's contract
    $responseEdit = $this->actingAs($this->ownerA)->get(route('owner.contracts.edit', $contractB));
    $responseEdit->assertForbidden();
});

test("3. owner cannot create contract for another owner's room", function () {
    $payload = [
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 2000000,
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasErrors(['property_id']);
    expect(Contract::where('room_id', $this->roomB1->id)->where('user_id', $this->tenantTest->id)->exists())->toBeFalse();
});

test('4. tenant not associated with room invitation is rejected', function () {
    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->unrelatedTenant->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 1750000,
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasErrors(['user_id']);
    expect(Contract::where('room_id', $this->roomTest->id)->where('user_id', $this->unrelatedTenant->id)->exists())->toBeFalse();
});

test('5. contract date validation requires end_date after start_date', function () {
    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->toDateString(), // before start_date
        'monthly_rent' => 1750000,
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasErrors(['end_date']);
});

test('6. invalid or negative rent price is rejected', function () {
    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
        'monthly_rent' => -500000, // Negative rent
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasErrors(['monthly_rent']);
});

test('7. overlapping active contracts on same room and period are rejected', function () {
    // Existing active contract: 2026-11-01 until 2027-04-30
    Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-EXISTING',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    // Overlapping attempt: 2026-12-01 to 2027-02-28
    $overlappingPayload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => '2026-12-01',
        'end_date' => '2027-02-28',
        'monthly_rent' => 1750000,
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $overlappingPayload);

    $response->assertSessionHasErrors(['room_id']);
});

test('8. draft contract does not force active room status when no active lease exists', function () {
    $this->invitationTest->delete(); // Remove reservation so room is truly clean
    $this->roomTest->update(['status' => 'kosong']);

    // Re-create accepted invitation so validation passes
    $inv = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'email' => $this->tenantTest->email,
        'name' => $this->tenantTest->name,
        'token' => 'token_draft_test_'.bin2hex(random_bytes(6)),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'monthly_rent' => 1750000,
        'status' => 'draft',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasNoErrors();
    $contract = Contract::where('room_id', $this->roomTest->id)->where('status', 'draft')->first();
    expect($contract)->not->toBeNull()
        ->and($contract->status)->toBe('draft');
});

test('9. active contract creation synchronizes room status to terisi', function () {
    $this->roomTest->update(['status' => 'kosong']);

    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 1750000,
        'status' => 'aktif',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);

    $response->assertSessionHasNoErrors();

    $this->roomTest->refresh();
    expect($this->roomTest->status)->toBe('terisi');
});

test('10. terminating or cancelling contract synchronizes room status to kosong when no other active lease', function () {
    $this->invitationTest->delete(); // Remove reservation

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-TERM',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $this->roomTest->update(['status' => 'terisi']);

    // Terminate contract
    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.terminate', $contract), [
        'termination_notes' => 'Kunci kamar telah diserahkan kembali.',
    ]);

    $response->assertRedirect(route('owner.contracts.show', $contract));

    $contract->refresh();
    $this->roomTest->refresh();

    expect($contract->status)->toBe('selesai')
        ->and($this->roomTest->status)->toBe('kosong');
});

test('11. other active contract prevents room from being marked vacant upon cancellation', function () {
    $this->invitationTest->delete();

    // Contract 1: Active
    $contract1 = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-1',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    // Contract 2: Active on next period
    $contract2 = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-2',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $this->roomTest->update(['status' => 'terisi']);

    // Cancel Contract 1
    $this->actingAs($this->ownerA)->post(route('owner.contracts.cancel', $contract1));

    $contract1->refresh();
    $this->roomTest->refresh();

    expect($contract1->status)->toBe('dibatalkan')
        ->and($this->roomTest->status)->toBe('terisi'); // Room must STAY terisi because contract2 is still aktif!
});

test('12. document upload succeeds with valid pdf/image file', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-DOC',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $file = UploadedFile::fake()->create('surat_sewa.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.documents.upload', $contract), [
        'document' => $file,
        'title' => 'Surat Perjanjian Sewa Asli',
    ]);

    $response->assertRedirect(route('owner.contracts.show', $contract));

    $doc = ContractDocument::where('contract_id', $contract->id)->first();
    expect($doc)->not->toBeNull()
        ->and($doc->file_name)->toBe('Surat Perjanjian Sewa Asli.pdf');

    Storage::disk('local')->assertExists($doc->file_path);
});

test('13. invalid file format is rejected', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-INVALIDDOC',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    // Executable/PHP file masquerading as document
    $file = UploadedFile::fake()->create('exploit.exe', 500, 'application/x-msdownload');

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.documents.upload', $contract), [
        'document' => $file,
    ]);

    $response->assertSessionHasErrors(['document']);
    expect(ContractDocument::where('contract_id', $contract->id)->exists())->toBeFalse();
});

test('14. unauthorized user cannot download contract document', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-AUTH',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 0,
        'deposit_status' => 'lunas',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $file = UploadedFile::fake()->create('kontrak_rahasia.pdf', 300, 'application/pdf');
    $path = Storage::disk('local')->putFile('contracts', $file);

    $document = ContractDocument::create([
        'contract_id' => $contract->id,
        'file_name' => 'kontrak_rahasia.pdf',
        'file_path' => $path,
        'file_size' => 300 * 1024,
        'mime_type' => 'application/pdf',
    ]);

    // Owner B (who does not own property A) tries to download
    $responseOwnerB = $this->actingAs($this->ownerB)->get(route('owner.contracts.documents.download', [$contract, $document]));
    $responseOwnerB->assertForbidden();

    // Owner A (the true owner) can download
    $responseOwnerA = $this->actingAs($this->ownerA)->get(route('owner.contracts.documents.download', [$contract, $document]));
    $responseOwnerA->assertOk();
});

test('15. contract renewal preserves old contract and creates linked new contract', function () {
    $originalContract = Contract::create([
        'contract_number' => 'CTR-TEST-PHASE5-ORIG',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 1750000,
        'total_amount' => 10500000,
        'deposit_amount' => 500000,
        'deposit_status' => 'sudah_dibayar',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    $renewalPayload = [
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'monthly_rent' => 1800000, // New rent rate
        'deposit_amount' => 0,
        'payment_method' => 'transfer_bank',
        'notes' => 'Perpanjangan sewa semester kedua.',
    ];

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.renew', $originalContract), $renewalPayload);

    $response->assertSessionHasNoErrors();

    // Original contract is retained
    $originalContract->refresh();
    expect($originalContract->status)->toBe('aktif')
        ->and($originalContract->contract_number)->toBe('CTR-TEST-PHASE5-ORIG');

    // New renewed contract exists
    $renewedContract = Contract::where('room_id', $this->roomTest->id)
        ->where('monthly_rent', 1800000)
        ->first();

    expect($renewedContract)->not->toBeNull()
        ->and($renewedContract->id)->not->toBe($originalContract->id)
        ->and($renewedContract->notes)->toContain('CTR-TEST-PHASE5-ORIG');
});

test('16. status changes and record creation fail atomically if an operation fails during transaction', function () {
    $this->roomTest->update(['status' => 'kosong']);

    // Mock RoomOccupancyService to throw an unexpected exception during sync
    $this->mock(RoomOccupancyService::class, function ($mock) {
        $mock->shouldReceive('getOverlappingContract')->andReturn(null);
        $mock->shouldReceive('syncRoomStatus')->andThrow(new \RuntimeException('Simulated failure during room status sync'));
    });

    $payload = [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomTest->id,
        'user_id' => $this->tenantTest->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'monthly_rent' => 1750000,
        'status' => 'aktif',
    ];

    try {
        $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), $payload);
    } catch (\RuntimeException $e) {
        // Exception caught as expected
    }

    // Verify atomic rollback: Room status is still kosong, and no contract was persisted
    $this->roomTest->refresh();
    expect($this->roomTest->status)->toBe('kosong')
        ->and(Contract::where('room_id', $this->roomTest->id)->exists())->toBeFalse();
});
