<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomOccupancyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->occupancyService = app(RoomOccupancyService::class);

    // Owner A & Property A
    $this->ownerA = User::create([
        'name' => 'Owner A Batch 2',
        'email' => 'owner_a_batch2_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Property A Batch 2 '.Str::random(6),
        'slug' => 'prop-a-batch2-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Integritas No. 1',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomA = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => 'A-'.rand(100, 999),
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'kosong',
    ]);

    // Owner B & Property B (unrelated owner)
    $this->ownerB = User::create([
        'name' => 'Owner B Batch 2',
        'email' => 'owner_b_batch2_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // Tenants
    $this->tenantA = User::create([
        'name' => 'Tenant A Batch 2',
        'email' => 'tenant_a_batch2_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Tenant B Batch 2',
        'email' => 'tenant_b_batch2_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Accepted invitation for Tenant A on Room A
    $this->invitationA = Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'name' => $this->tenantA->name,
        'email' => $this->tenantA->email,
        'phone' => $this->tenantA->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);
});

/*
|--------------------------------------------------------------------------
| B-2: Concurrency & Overlap Race Condition Prevention
|--------------------------------------------------------------------------
*/

test('B2-1. store prevents overlapping active contracts under lock inside database transaction', function () {
    // Existing active contract: 2026-01-01 to 2026-06-30
    Contract::create([
        'contract_number' => 'CTR-TEST-EX-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    // Tenant B accepts an invitation for Room A
    Invitation::create([
        'invited_by' => $this->ownerA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantB->id,
        'name' => $this->tenantB->name,
        'email' => $this->tenantB->email,
        'phone' => $this->tenantB->phone,
        'token' => Str::random(40),
        'status' => 'diterima',
        'valid_until' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    // Attempt to store overlapping active contract: 2026-05-01 to 2026-10-31
    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantB->id,
        'start_date' => '2026-05-01',
        'end_date' => '2026-10-31',
        'monthly_rent' => 2000000,
        'status' => 'aktif',
    ]);

    $response->assertSessionHasErrors(['room_id']);
    // Verify only the 1 original contract exists
    expect(Contract::where('room_id', $this->roomA->id)->count())->toBe(1);
});

test('B2-2. store rejects contract creation if room is marked perbaikan under lock', function () {
    $this->roomA->update(['status' => 'perbaikan']);

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'monthly_rent' => 2000000,
        'status' => 'aktif',
    ]);

    $response->assertSessionHasErrors(['room_id']);
    expect(Contract::where('room_id', $this->roomA->id)->count())->toBe(0);
});

test('B2-3. renew rejects conflicting active contract under room lock', function () {
    $contractA = Contract::create([
        'contract_number' => 'CTR-TEST-RN-A-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    // Another active future contract already exists for this room: 2026-08-01 to 2026-12-31
    Contract::create([
        'contract_number' => 'CTR-TEST-RN-B-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantB->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-12-31',
        'duration_months' => 5,
        'monthly_rent' => 2000000,
        'total_amount' => 10000000,
        'status' => 'aktif',
    ]);

    // Attempt to renew contractA with dates overlapping the future contract: 2026-07-01 to 2026-09-30
    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.renew', $contractA), [
        'start_date' => '2026-07-01',
        'end_date' => '2026-09-30',
        'monthly_rent' => 2000000,
    ]);

    $response->assertSessionHasErrors(['start_date']);
    expect(Contract::where('room_id', $this->roomA->id)->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| B-4: Financial History Integrity
|--------------------------------------------------------------------------
*/

test('B4-1. editing draft contract updates dates and totals normally', function () {
    $draft = Contract::create([
        'contract_number' => 'CTR-DRAFT-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-05-01',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->ownerA)->put(route('owner.contracts.update', $draft), [
        'start_date' => '2026-02-01',
        'end_date' => '2026-08-01',
        'duration_months' => 6,
        'monthly_rent' => 2100000,
        'status' => 'draft',
    ]);

    $response->assertSessionHasNoErrors();
    $draft->refresh();
    expect($draft->duration_months)->toBe(6)
        ->and((float) $draft->monthly_rent)->toBe(2100000.0)
        ->and((float) $draft->total_amount)->toBe(12600000.0);
});

test('B4-2. editing active contract without financial records updates end date and total normally', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-ACTIVE-NOBILLS-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->ownerA)->put(route('owner.contracts.update', $contract), [
        'end_date' => '2026-08-31',
        'duration_months' => 8,
    ]);

    $response->assertSessionHasNoErrors();
    $contract->refresh();
    expect($contract->end_date->toDateString())->toBe('2026-08-31')
        ->and($contract->duration_months)->toBe(8)
        ->and((float) $contract->total_amount)->toBe(16000000.0);
});

test('B4-3. altering financial terms or end date when bills exist is rejected and leaves values unchanged', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-ACTIVE-WITHBILLS-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'deposit_amount' => 1000000,
        'status' => 'aktif',
    ]);

    // Issue a bill under this contract
    Bill::create([
        'bill_number' => 'BILL-TEST-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-01',
        'issue_date' => '2026-01-01',
        'due_date' => '2026-01-10',
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => 'lunas',
    ]);

    // Attempt to alter end_date which would retroactively change total_amount
    $response = $this->actingAs($this->ownerA)->put(route('owner.contracts.update', $contract), [
        'end_date' => '2026-12-31',
        'duration_months' => 12,
        'notes' => 'Mencoba ubah tanggal sewa',
    ]);

    $response->assertSessionHasErrors(['end_date']);

    // Contract remains strictly unchanged
    $contract->refresh();
    expect($contract->end_date->toDateString())->toBe('2026-06-30')
        ->and($contract->duration_months)->toBe(6)
        ->and((float) $contract->total_amount)->toBe(12000000.0);
});

test('B4-4. updating non-financial fields on contract with bills is permitted without corrupting totals', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-NOTES-WITHBILLS-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'deposit_amount' => 1000000,
        'status' => 'aktif',
        'notes' => 'Catatan awal',
    ]);

    Bill::create([
        'bill_number' => 'BILL-TEST-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-01',
        'issue_date' => '2026-01-01',
        'due_date' => '2026-01-10',
        'total_amount' => 2000000,
        'status' => 'lunas',
    ]);

    // Keep end_date and deposit unchanged, but update notes
    $response = $this->actingAs($this->ownerA)->put(route('owner.contracts.update', $contract), [
        'end_date' => '2026-06-30',
        'deposit_amount' => 1000000,
        'notes' => 'Catatan diperbarui setelah konfirmasi pembayaran listrik.',
    ]);

    $response->assertSessionHasNoErrors();
    $contract->refresh();
    expect($contract->notes)->toBe('Catatan diperbarui setelah konfirmasi pembayaran listrik.')
        ->and((float) $contract->total_amount)->toBe(12000000.0);
});

/*
|--------------------------------------------------------------------------
| C-1: Private Storage Hardening & Download Authorization
|--------------------------------------------------------------------------
*/

test('C1-1. automatic framework storage file-serving routes are absent for private disk', function () {
    // The route `storage.local` (/storage/{path}) must NOT be registered
    expect(Route::has('storage.local'))->toBeFalse();
    expect(Route::has('storage.local.upload'))->toBeFalse();

    // Verify requesting /storage/contracts/test.pdf does not hit the private disk
    $response = $this->get('/storage/contracts/test.pdf');
    expect($response->status())->toBe(404);
});

test('C1-2. authorized owner and assigned tenant can download contract document, while unauthorized users are rejected', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-DOCS-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    $storedPath = Storage::disk('local')->put('contracts/test_doc.pdf', 'CONFIDENTIAL CONTRACT CONTENT');

    $document = ContractDocument::create([
        'contract_id' => $contract->id,
        'file_name' => 'surat_perjanjian.pdf',
        'file_path' => 'contracts/test_doc.pdf',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
    ]);

    $downloadUrl = route('owner.contracts.documents.download', [$contract, $document]);

    // 1. Owning Owner can download via Owner route
    $resOwner = $this->actingAs($this->ownerA)->get($downloadUrl);
    $resOwner->assertStatus(200);

    // 2. Unrelated Owner cannot download (403 from Gate policy)
    $resOwnerB = $this->actingAs($this->ownerB)->get($downloadUrl);
    $resOwnerB->assertStatus(403);

    // 3. Tenant access on owner route is rejected by role:owner middleware (403)
    $resTenantA = $this->actingAs($this->tenantA)->get($downloadUrl);
    $resTenantA->assertStatus(403);

    $resTenantB = $this->actingAs($this->tenantB)->get($downloadUrl);
    $resTenantB->assertStatus(403);

    // 4. Verify policy layer explicitly authorizes assigned tenant and denies unrelated tenant
    expect(Gate::forUser($this->ownerA)->allows('downloadDocument', $contract))->toBeTrue();
    expect(Gate::forUser($this->tenantA)->allows('downloadDocument', $contract))->toBeTrue();
    expect(Gate::forUser($this->ownerB)->allows('downloadDocument', $contract))->toBeFalse();
    expect(Gate::forUser($this->tenantB)->allows('downloadDocument', $contract))->toBeFalse();

    // 5. Mismatched contract ID returns 404
    $otherContract = Contract::create([
        'contract_number' => 'CTR-OTHER-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-06-30',
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'selesai',
    ]);

    $mismatchedUrl = route('owner.contracts.documents.download', [$otherContract, $document]);
    $resMismatch = $this->actingAs($this->ownerA)->get($mismatchedUrl);
    $resMismatch->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| C-2: Protect Historical Contract Documents
|--------------------------------------------------------------------------
*/

test('C2-1. document deletion on completed or cancelled contract is rejected and leaves file and record intact', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-FINISHED-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-06-30',
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'selesai',
    ]);

    Storage::disk('local')->put('contracts/historical_doc.pdf', 'HISTORICAL AGREEMENT');

    $document = ContractDocument::create([
        'contract_id' => $contract->id,
        'file_name' => 'surat_perjanjian_lama.pdf',
        'file_path' => 'contracts/historical_doc.pdf',
        'file_size' => 2048,
        'mime_type' => 'application/pdf',
    ]);

    $deleteUrl = route('owner.contracts.documents.destroy', [$contract, $document]);

    $response = $this->actingAs($this->ownerA)->delete($deleteUrl);

    $response->assertSessionHas('error');

    // Record and file must remain strictly intact
    expect(ContractDocument::where('id', $document->id)->exists())->toBeTrue();
    expect(Storage::disk('local')->exists('contracts/historical_doc.pdf'))->toBeTrue();
});

test('C2-2. authorized deletion of document on active contract removes both database record and file', function () {
    Storage::fake('local');

    $contract = Contract::create([
        'contract_number' => 'CTR-ACT-DEL-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    Storage::disk('local')->put('contracts/removable_doc.pdf', 'DRAFT SCAN');

    $document = ContractDocument::create([
        'contract_id' => $contract->id,
        'file_name' => 'draft_scan.pdf',
        'file_path' => 'contracts/removable_doc.pdf',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
    ]);

    $deleteUrl = route('owner.contracts.documents.destroy', [$contract, $document]);

    $response = $this->actingAs($this->ownerA)->delete($deleteUrl);

    $response->assertSessionHas('success');

    // Both database record and file must be deleted
    expect(ContractDocument::where('id', $document->id)->exists())->toBeFalse();
    expect(Storage::disk('local')->exists('contracts/removable_doc.pdf'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| C-3: Orphan File Cleanup on Database Failure
|--------------------------------------------------------------------------
*/

test('C3-1. newly uploaded document is deleted from storage if contract creation fails after file storage', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('ktp_penyewa.pdf', 500, 'application/pdf');

    // Simulate an unexpected database failure during room status sync or insertion
    $this->mock(RoomOccupancyService::class, function ($mock) {
        $mock->shouldReceive('getOverlappingContract')->andReturn(null);
        $mock->shouldReceive('syncRoomStatus')->andThrow(new \RuntimeException('Simulated database crash during contract creation'));
    });

    $response = $this->actingAs($this->ownerA)->post(route('owner.contracts.store'), [
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'monthly_rent' => 2000000,
        'status' => 'aktif',
        'document' => $file,
    ]);

    expect(Contract::where('room_id', $this->roomA->id)->exists())->toBeFalse();
    expect(ContractDocument::where('file_name', 'ktp_penyewa.pdf')->exists())->toBeFalse();

    // Storage must have cleaned up the uploaded file: 0 files in contracts folder
    $files = Storage::disk('local')->allFiles('contracts');
    expect($files)->toBeEmpty();
});
