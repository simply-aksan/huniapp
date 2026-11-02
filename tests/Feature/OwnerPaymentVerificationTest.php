<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\BillService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->billService = app(BillService::class);

    // Owner A & Property A & Room A1 & Room A2
    $this->ownerA = User::create([
        'name' => 'Owner Alpha',
        'email' => 'owner_verif_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Graha Alpha '.Str::random(5),
        'slug' => 'kos-graha-alpha-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Kebon Jeruk No. 10',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 10,
        'status' => 'aktif',
    ]);

    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '101',
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'terisi',
    ]);

    // Owner B & Property B & Room B1 (Unrelated Owner)
    $this->ownerB = User::create([
        'name' => 'Owner Beta',
        'email' => 'owner_verif_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Beta Residence '.Str::random(5),
        'slug' => 'kos-beta-residence-'.Str::random(8),
        'type' => 'putra',
        'address' => 'Jl. Anggrek No. 5',
        'city' => 'Jakarta Selatan',
        'district' => 'Kebayoran Baru',
        'postal_code' => '12110',
        'total_rooms' => 8,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => '201',
        'floor' => 2,
        'type' => 'standar',
        'price' => 1800000,
        'status' => 'terisi',
    ]);

    // Tenants
    $this->tenantA = User::create([
        'name' => 'Budi Penyewa',
        'email' => 'budi_verif_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Siti Penyewa',
        'email' => 'siti_verif_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Contracts
    $this->contractA = Contract::create([
        'contract_number' => 'CTR-VER-A-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'deposit_amount' => 500000,
        'deposit_status' => 'belum_lunas',
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    $this->contractB = Contract::create([
        'contract_number' => 'CTR-VER-B-'.Str::random(6),
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenantB->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'deposit_amount' => 600000,
        'deposit_status' => 'belum_lunas',
        'total_amount' => 10800000,
        'status' => 'aktif',
    ]);
});

/*
|--------------------------------------------------------------------------
| Section 1: Listing & Detail Authorization (Tests 1-5)
|--------------------------------------------------------------------------
*/

test('1. owner can view payments list belonging to their properties', function () {
    $billA = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Bulan Oktober 2026', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-VER-001',
        'bill_id' => $billA->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->get(route('owner.payments.index'));

    $response->assertOk()
        ->assertSee('PAY-VER-001')
        ->assertSee('Budi Penyewa')
        ->assertSee('Kos Graha Alpha')
        ->assertSee('1.500.000');
});

test('2. owner cannot view payments belonging to another owner', function () {
    $billA = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Alpha', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $billB = $this->billService->createInvoice([
        'contract_id' => $this->contractB->id,
        'user_id' => $this->tenantB->id,
        'room_id' => $this->roomB1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Beta', 'amount' => 1800000, 'quantity' => 1],
    ]);

    $paymentA = Payment::create([
        'payment_number' => 'PAY-ALPHA-001',
        'bill_id' => $billA->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $paymentB = Payment::create([
        'payment_number' => 'PAY-BETA-001',
        'bill_id' => $billB->id,
        'user_id' => $this->tenantB->id,
        'amount' => 1800000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    // Owner A views list
    $response = $this->actingAs($this->ownerA)->get(route('owner.payments.index'));
    $response->assertOk()
        ->assertSee('PAY-ALPHA-001')
        ->assertDontSee('PAY-BETA-001');

    // Owner A attempts to view Owner B's payment detail directly
    $this->actingAs($this->ownerA)
        ->get(route('owner.payments.show', $paymentB))
        ->assertForbidden();
});

test('3. owner can view pending payment detail and invoice summary', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar 101', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-DETAIL-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'BCA Virtual Account',
        'reference_number' => 'REF-BCA-123456',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
        'notes' => 'Tolong diverifikasi secepatnya ya pak kos.',
    ]);

    $response = $this->actingAs($this->ownerA)->get(route('owner.payments.show', $payment));

    $response->assertOk()
        ->assertSee('PAY-DETAIL-001')
        ->assertSee('Budi Penyewa')
        ->assertSee('Kos Graha Alpha')
        ->assertSee('101')
        ->assertSee('REF-BCA-123456')
        ->assertSee('Tolong diverifikasi secepatnya ya pak kos.')
        ->assertSee('1.500.000');
});

test('4. authorized owner can access and download payment proof', function () {
    Storage::fake('local');

    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-PROOF-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');
    $storedPath = Storage::disk('local')->putFile('payments', $file);

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_transfer.jpg',
        'file_path' => $storedPath,
        'file_size' => 500 * 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.payments.proofs.download', [$payment, $proof]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('bukti_transfer.jpg');
});

test('5. unauthorized owner cannot download payment proof', function () {
    Storage::fake('local');

    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-PROOF-002',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $file = UploadedFile::fake()->create('bukti_alpha.jpg', 500, 'image/jpeg');
    $storedPath = Storage::disk('local')->putFile('payments', $file);

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_alpha.jpg',
        'file_path' => $storedPath,
        'file_size' => 500 * 1024,
        'mime_type' => 'image/jpeg',
    ]);

    // Owner B attempts to download Owner A's tenant's proof
    $response = $this->actingAs($this->ownerB)
        ->get(route('owner.payments.proofs.download', [$payment, $proof]));

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Section 2: Approval Workflow & Reconciliation (Tests 6-11)
|--------------------------------------------------------------------------
*/

test('6. valid approval updates bills paid_amount exactly once', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-APP-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    expect((float) $bill->paid_amount)->toBe(0.0);

    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment));

    $response->assertRedirect(route('owner.payments.show', $payment));

    $freshPayment = $payment->fresh();
    $freshBill = $bill->fresh();

    expect($freshPayment->status)->toBe(PaymentStatus::Berhasil->value);
    expect($freshPayment->verified_by)->toBe($this->ownerA->id);
    expect($freshPayment->verified_at)->not->toBeNull();

    expect((float) $freshBill->paid_amount)->toBe(1500000.0);
    expect($freshBill->status)->toBe(BillStatus::Lunas->value);
});

test('7. partial payment approval leaves correct remaining balance', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-PARTIAL-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment));

    $response->assertRedirect(route('owner.payments.show', $payment));

    $freshBill = $bill->fresh();
    expect((float) $freshBill->paid_amount)->toBe(500000.0);
    expect($freshBill->status)->toBe(BillStatus::Sebagian->value);
    expect($freshBill->outstandingAmount())->toBe(1000000.0);
});

test('8. final approval marks invoice as lunas', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // First payment was already verified: 1.000.000
    Payment::create([
        'payment_number' => 'PAY-PREV-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1000000,
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerA->id,
        'verified_at' => now()->subDay(),
        'payment_date' => now()->subDay(),
    ]);

    $bill->update([
        'paid_amount' => 1000000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // Second payment: 500.000
    $payment2 = Payment::create([
        'payment_number' => 'PAY-FINAL-002',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment2));
    $response->assertRedirect(route('owner.payments.show', $payment2));

    $freshBill = $bill->fresh();
    expect((float) $freshBill->paid_amount)->toBe(1500000.0);
    expect($freshBill->status)->toBe(BillStatus::Lunas->value);
    expect($freshBill->isPaid())->toBeTrue();
    expect($freshBill->outstandingAmount())->toBe(0.0);
});

test('9. second approval is rejected without mutating balance', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-IDEM-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    // 1st approval
    $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment))->assertRedirect();
    expect((float) $bill->fresh()->paid_amount)->toBe(1500000.0);

    // 2nd approval attempt on already approved payment
    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment));
    $response->assertSessionHasErrors('payment');

    // Balance MUST remain 1.500.000 (not 3.000.000)
    expect((float) $bill->fresh()->paid_amount)->toBe(1500000.0);
});

test('10. rejected payment cannot be approved', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-REJ-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Ditolak->value,
        'rejection_reason' => 'Foto bukti tidak terbaca',
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment));

    $response->assertSessionHasErrors('payment');
    expect((float) $bill->fresh()->paid_amount)->toBe(0.0);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Ditolak->value);
});

test('11. non-pending payment cannot be approved or rejected again', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // Already successful payment
    $payment = Payment::create([
        'payment_number' => 'PAY-SUCC-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'status' => PaymentStatus::Berhasil->value,
        'payment_date' => now(),
    ]);

    // Attempting to reject an already approved payment fails
    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.reject', $payment), [
        'rejection_reason' => 'Mencoba menolak transaksi yang sudah berhasil',
    ]);

    $response->assertSessionHasErrors('payment');
    expect($payment->fresh()->status)->toBe(PaymentStatus::Berhasil->value);
});

/*
|--------------------------------------------------------------------------
| Section 3: Rejection Workflow & Re-submission (Tests 12-14)
|--------------------------------------------------------------------------
*/

test('12. rejection requires a valid reason', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-REJ-TEST',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    // 1. Empty rejection reason
    $this->actingAs($this->ownerA)->post(route('owner.payments.reject', $payment), [
        'rejection_reason' => '',
    ])->assertSessionHasErrors('rejection_reason');

    // 2. Too short (< 5 chars)
    $this->actingAs($this->ownerA)->post(route('owner.payments.reject', $payment), [
        'rejection_reason' => 'xyz',
    ])->assertSessionHasErrors('rejection_reason');

    // 3. Valid reason
    $this->actingAs($this->ownerA)->post(route('owner.payments.reject', $payment), [
        'rejection_reason' => 'Nomor referensi transfer tidak ditemukan di mutasi bank.',
    ])->assertRedirect(route('owner.payments.show', $payment));

    $freshPayment = $payment->fresh();
    expect($freshPayment->status)->toBe(PaymentStatus::Ditolak->value);
    expect($freshPayment->rejection_reason)->toBe('Nomor referensi transfer tidak ditemukan di mutasi bank.');
    expect($freshPayment->verified_by)->toBe($this->ownerA->id);
    expect($freshPayment->verified_at)->not->toBeNull();
});

test('13. rejection does not modify bills paid_amount and preserves proof history', function () {
    Storage::fake('local');

    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-REJ-AUDIT',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $file = UploadedFile::fake()->create('bukti_buram.jpg', 500, 'image/jpeg');
    $storedPath = Storage::disk('local')->putFile('payments', $file);

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_buram.jpg',
        'file_path' => $storedPath,
        'file_size' => 500 * 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($this->ownerA)->post(route('owner.payments.reject', $payment), [
        'rejection_reason' => 'Gambar buram dan nomor transaksi terpotong.',
    ])->assertRedirect();

    expect((float) $bill->fresh()->paid_amount)->toBe(0.0);
    expect($bill->fresh()->status)->toBe(BillStatus::MenungguPembayaran->value);

    // Payment record & proof file MUST be preserved!
    expect(Payment::where('id', $payment->id)->exists())->toBeTrue();
    expect(PaymentProof::where('id', $proof->id)->exists())->toBeTrue();
    expect(Storage::disk('local')->exists($storedPath))->toBeTrue();
});

test('14. tenant can submit new payment after rejection', function () {
    Storage::fake('local');

    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // Initial payment rejected
    $payment1 = Payment::create([
        'payment_number' => 'PAY-REJ-TENANT',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Ditolak->value,
        'rejection_reason' => 'Mohon sertakan bukti transfer resmi bank, bukan screenshot chat.',
        'payment_date' => now()->subHours(2),
    ]);

    expect($bill->fresh()->hasPendingPayment())->toBeFalse();

    $newFile = UploadedFile::fake()->create('bukti_resmi.jpg', 500, 'image/jpeg');

    // Tenant submits new payment attempt
    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => '2026-10-02',
        'reference_number' => 'REF-RESMI-999',
        'notes' => 'Ini bukti resmi mutasi m-banking.',
        'proofs' => [$newFile],
    ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));

    $payments = Payment::where('bill_id', $bill->id)->get();
    expect($payments)->toHaveCount(2);

    $latestPayment = $payments->sortByDesc('id')->first();
    expect($latestPayment->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
    expect($latestPayment->reference_number)->toBe('REF-RESMI-999');
});

/*
|--------------------------------------------------------------------------
| Section 4: Concurrency Safety & Overpayment Prevention (Tests 15-18)
|--------------------------------------------------------------------------
*/

test('15. approval causing overpayment is rejected', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1000000, 'quantity' => 1],
    ]);

    // Already paid 800.000, outstanding is only 200.000
    $bill->update([
        'paid_amount' => 800000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // A payment for 500.000 (which exceeds the 200.000 remaining balance)
    $payment = Payment::create([
        'payment_number' => 'PAY-OVER-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment));

    $response->assertSessionHasErrors('payment');

    // paid_amount MUST remain 800.000, NOT 1.300.000
    expect((float) $bill->fresh()->paid_amount)->toBe(800000.0);
    expect($payment->fresh()->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
});

test('16. two pending payments on the same invoice cannot produce an overpayment', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1000000, 'quantity' => 1],
    ]);

    // Two pending payments of 600.000 each (total 1.200.000 > 1.000.000)
    $payment1 = Payment::create([
        'payment_number' => 'PAY-RACE-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 600000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $payment2 = Payment::create([
        'payment_number' => 'PAY-RACE-002',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 600000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    // 1. Approve Payment 1: 600.000 succeeds
    $resp1 = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment1));
    $resp1->assertRedirect();

    expect((float) $bill->fresh()->paid_amount)->toBe(600000.0);
    expect($bill->fresh()->outstandingAmount())->toBe(400000.0);

    // 2. Attempt to approve Payment 2: 600.000 exceeds remaining 400.000
    $resp2 = $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment2));
    $resp2->assertSessionHasErrors('payment');

    // Balance remains exactly 600.000, strictly preventing overpayment
    expect((float) $bill->fresh()->paid_amount)->toBe(600000.0);
    expect($payment2->fresh()->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
});

test('17. approved deposit payment updates deposit bill and contract deposit_status correctly', function () {
    // Generate deposit invoice for Contract A (deposit_amount: 500.000)
    $invoices = $this->billService->generateInitialInvoices($this->contractA);
    $depositBill = $invoices['deposit'];
    expect($depositBill)->not->toBeNull();
    expect((float) $depositBill->total_amount)->toBe(500000.0);
    expect($this->contractA->fresh()->deposit_status)->toBe('belum_lunas');

    // 1. Partial deposit payment: 200.000
    $partialPayment = Payment::create([
        'payment_number' => 'PAY-DEP-PARTIAL',
        'bill_id' => $depositBill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 200000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $partialPayment))->assertRedirect();

    expect((float) $depositBill->fresh()->paid_amount)->toBe(200000.0);
    expect($depositBill->fresh()->status)->toBe(BillStatus::Sebagian->value);
    // Contract deposit_status must NOT be lunas yet!
    expect($this->contractA->fresh()->deposit_status)->toBe('belum_lunas');

    // 2. Remaining deposit payment: 300.000
    $finalPayment = Payment::create([
        'payment_number' => 'PAY-DEP-FINAL',
        'bill_id' => $depositBill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 300000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $finalPayment))->assertRedirect();

    expect((float) $depositBill->fresh()->paid_amount)->toBe(500000.0);
    expect($depositBill->fresh()->status)->toBe(BillStatus::Lunas->value);

    // Contract deposit_status is now synchronized to 'lunas'
    expect($this->contractA->fresh()->deposit_status)->toBe('lunas');
    // Contract status remains 'aktif'
    expect($this->contractA->fresh()->status)->toBe('aktif');
});

test('18. approved additional invoice payment follows the same reconciliation rules', function () {
    $additionalBill = $this->billService->generateAdditionalInvoice(
        $this->contractA,
        'Penggantian Kunci & Kartu Akses',
        150000
    );

    expect((float) $additionalBill->total_amount)->toBe(150000.0);
    expect((float) $additionalBill->paid_amount)->toBe(0.0);

    $payment = Payment::create([
        'payment_number' => 'PAY-ADD-001',
        'bill_id' => $additionalBill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 150000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment))->assertRedirect();

    $freshBill = $additionalBill->fresh();
    expect((float) $freshBill->paid_amount)->toBe(150000.0);
    expect($freshBill->status)->toBe(BillStatus::Lunas->value);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Berhasil->value);
});

/*
|--------------------------------------------------------------------------
| Section 5: Security & Full Regression (Tests 19-20)
|--------------------------------------------------------------------------
*/

test('19. client financial field manipulation during approval cannot alter server balance or status', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-SPOOF-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    // Client maliciously attempts to send a spoofed amount and status in POST body
    $this->actingAs($this->ownerA)->post(route('owner.payments.approve', $payment), [
        'amount' => 10,
        'paid_amount' => 999999999,
        'status' => 'menunggu_verifikasi',
        'verified_by' => 9999,
        'verified_at' => '2020-01-01 00:00:00',
    ])->assertRedirect();

    $freshBill = $bill->fresh();
    $freshPayment = $payment->fresh();

    // The server MUST use the actual payment record amount (1.500.000)
    expect((float) $freshBill->paid_amount)->toBe(1500000.0);
    expect($freshBill->status)->toBe(BillStatus::Lunas->value);

    // The payment verifier MUST be the authenticated owner, verified_at must be current time
    expect($freshPayment->verified_by)->toBe($this->ownerA->id);
    expect($freshPayment->verified_at->year)->toBe(now()->year);
    expect($freshPayment->status)->toBe(PaymentStatus::Berhasil->value);
});

test('20. full regression across contract, bill, payment submission, and owner verification lifecycle', function () {
    Storage::fake('local');

    // 1. Initial invoices generated on contract
    $invoices = $this->billService->generateInitialInvoices($this->contractA);
    $rentBill = $invoices['rent'];
    $depositBill = $invoices['deposit'];

    expect($rentBill)->not->toBeNull();
    expect($depositBill)->not->toBeNull();
    expect((float) $rentBill->total_amount)->toBe(1500000.0);
    expect((float) $depositBill->total_amount)->toBe(500000.0);

    // 2. Tenant submits rent payment with proof
    $rentProofFile = UploadedFile::fake()->create('bukti_rent.jpg', 500, 'image/jpeg');
    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $rentBill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => '2026-10-02',
        'reference_number' => 'REF-RENT-101',
        'proofs' => [$rentProofFile],
    ])->assertRedirect();

    $rentPayment = Payment::where('bill_id', $rentBill->id)->firstOrFail();
    expect($rentPayment->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
    expect($rentPayment->proofs)->toHaveCount(1);

    // 3. Owner views rent payment in index and detail
    $this->actingAs($this->ownerA)
        ->get(route('owner.payments.index'))
        ->assertOk()
        ->assertSee($rentPayment->payment_number);

    $this->actingAs($this->ownerA)
        ->get(route('owner.payments.show', $rentPayment))
        ->assertOk()
        ->assertSee('REF-RENT-101');

    // 4. Owner approves rent payment
    $this->actingAs($this->ownerA)
        ->post(route('owner.payments.approve', $rentPayment))
        ->assertRedirect();

    expect((float) $rentBill->fresh()->paid_amount)->toBe(1500000.0);
    expect($rentBill->fresh()->status)->toBe(BillStatus::Lunas->value);
    expect($rentBill->fresh()->isPaid())->toBeTrue();

    // 5. Tenant submits deposit payment with proof
    $depProofFile = UploadedFile::fake()->create('bukti_deposit.jpg', 500, 'image/jpeg');
    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $depositBill), [
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => '2026-10-02',
        'reference_number' => 'REF-DEP-101',
        'proofs' => [$depProofFile],
    ])->assertRedirect();

    $depPayment = Payment::where('bill_id', $depositBill->id)->firstOrFail();

    // 6. Owner approves deposit payment
    $this->actingAs($this->ownerA)
        ->post(route('owner.payments.approve', $depPayment))
        ->assertRedirect();

    expect((float) $depositBill->fresh()->paid_amount)->toBe(500000.0);
    expect($depositBill->fresh()->status)->toBe(BillStatus::Lunas->value);
    expect($this->contractA->fresh()->deposit_status)->toBe('lunas');
    expect($this->contractA->fresh()->status)->toBe('aktif');

    // 7. Unrelated Owner B cannot access either payment
    $this->actingAs($this->ownerB)
        ->get(route('owner.payments.show', $rentPayment))
        ->assertForbidden();

    $this->actingAs($this->ownerB)
        ->get(route('owner.payments.show', $depPayment))
        ->assertForbidden();
});
