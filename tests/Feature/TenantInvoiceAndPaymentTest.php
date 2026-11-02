<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
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

    // Owner A & Property A & Room A1
    $this->ownerA = User::create([
        'name' => 'Owner Alpha',
        'email' => 'owner_ten_a_'.Str::random(8).'@huniapp.test',
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
        'email' => 'owner_ten_b_'.Str::random(8).'@huniapp.test',
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
        'email' => 'budi_ten_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Siti Penyewa',
        'email' => 'siti_ten_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Contracts
    $this->contractA = Contract::create([
        'contract_number' => 'CTR-TEN-A-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'deposit_amount' => 500000,
        'total_amount' => 9000000,
        'status' => 'aktif',
    ]);

    $this->contractB = Contract::create([
        'contract_number' => 'CTR-TEN-B-'.Str::random(6),
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenantB->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'deposit_amount' => 600000,
        'total_amount' => 10800000,
        'status' => 'aktif',
    ]);
});

/*
|--------------------------------------------------------------------------
| Section 1: Tenant Invoice Access (Tests 1-5)
|--------------------------------------------------------------------------
*/

test('1. tenant can view their own invoice list', function () {
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

    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.index'));

    $response->assertOk()
        ->assertSee($billA->bill_number)
        ->assertSee('Kos Graha Alpha')
        ->assertSee('1.500.000');
});

test('2. tenant cannot view another tenant invoice in list', function () {
    $billA = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Tagihan Budi', 'amount' => 1500000, 'quantity' => 1],
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
        ['description' => 'Tagihan Siti Rahma', 'amount' => 1800000, 'quantity' => 1],
    ]);

    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.index'));

    $response->assertOk()
        ->assertSee($billA->bill_number)
        ->assertDontSee($billB->bill_number);
});

test('3. tenant cannot access another tenant invoice detail', function () {
    $billB = $this->billService->createInvoice([
        'contract_id' => $this->contractB->id,
        'user_id' => $this->tenantB->id,
        'room_id' => $this->roomB1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Tagihan Rahasia Siti', 'amount' => 1800000, 'quantity' => 1],
    ]);

    // Tenant A attempts to view Tenant B's invoice
    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.show', $billB));

    $response->assertForbidden();
});

test('4. tenant can view their own line items and payment history', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Jasa Cuci AC Kamar', 'amount' => 120000, 'quantity' => 1],
        ['description' => 'Token Listrik Tambahan', 'amount' => 50000, 'quantity' => 2],
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-TEST-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 100000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Ditolak->value,
        'payment_date' => now(),
        'rejection_reason' => 'Bukti mutasi rekening tidak terbaca',
    ]);

    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.show', $bill));

    $response->assertOk()
        ->assertSee('Jasa Cuci AC Kamar')
        ->assertSee('Token Listrik Tambahan')
        ->assertSee('PAY-TEST-001')
        ->assertSee('Bukti mutasi rekening tidak terbaca');
});

test('5. pagination and filters preserve valid query parameters', function () {
    for ($i = 1; $i <= 12; $i++) {
        $this->billService->createInvoice([
            'contract_id' => $this->contractA->id,
            'user_id' => $this->tenantA->id,
            'room_id' => $this->roomA1->id,
            'type' => BillType::Tambahan->value,
            'billing_period' => '2026-10',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-08',
        ], [
            ['description' => "Item Penagihan #{$i}", 'amount' => 20000 * $i, 'quantity' => 1],
        ]);
    }

    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.index', [
        'type' => BillType::Tambahan->value,
        'status' => BillStatus::MenungguPembayaran->value,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    expect($content)->toContain('type=tambahan');
    expect($content)->toContain('status=menunggu_pembayaran');
});

/*
|--------------------------------------------------------------------------
| Section 2: Payment Submission (Tests 6-15)
|--------------------------------------------------------------------------
*/

test('6. valid payment submission creates a payment with status menunggu_verifikasi', function () {
    Storage::fake('local');

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

    $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');

    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => '2026-10-02',
        'reference_number' => 'TRX-BCA-987654',
        'notes' => 'Pembayaran lunas sewa bulan Oktober',
        'proofs' => [$file],
    ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));

    $payment = Payment::where('bill_id', $bill->id)->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe(1500000.0);
    expect($payment->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
    expect($payment->payment_number)->toMatch('/^PAY-\d{6}-\d{4}$/');
    expect($payment->proofs)->toHaveCount(1);
    expect(Storage::disk('local')->exists($payment->proofs->first()->file_path))->toBeTrue();
});

test('7. a zero or negative payment amount is rejected', function () {
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

    $file = UploadedFile::fake()->create('bukti.png', 500, 'image/png');

    // 1. Zero amount
    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 0,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ])->assertSessionHasErrors('amount');

    // 2. Negative amount
    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => -50000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ])->assertSessionHasErrors('amount');
});

test('8. an amount exceeding the outstanding balance is rejected', function () {
    Storage::fake('local');

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

    $file = UploadedFile::fake()->create('bukti.png', 500, 'image/png');

    // Submit 1.500.000 for a 1.000.000 bill
    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Payment::where('bill_id', $bill->id)->exists())->toBeFalse();
});

test('9. cancelled and fully paid invoices reject new submissions', function () {
    Storage::fake('local');

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

    $file = UploadedFile::fake()->create('bukti.png', 500, 'image/png');

    // 1. Paid bill
    $bill->update([
        'status' => BillStatus::Lunas->value,
        'paid_amount' => 1000000,
    ]);

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 100000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ])->assertSessionHasErrors('amount');

    // 2. Cancelled bill
    $bill->update([
        'status' => BillStatus::Dibatalkan->value,
        'paid_amount' => 0,
    ]);

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ])->assertSessionHasErrors('amount');
});

test('10. another pending payment blocks duplicate submission', function () {
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

    // First payment pending verification
    Payment::create([
        'payment_number' => 'PAY-PENDING-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 750000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $file = UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg');

    // Attempt second submission while first is still pending
    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 750000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Payment::where('bill_id', $bill->id)->count())->toBe(1);
});

test('11. a rejected payment permits a new attempt', function () {
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

    // Past rejected payment
    Payment::create([
        'payment_number' => 'PAY-REJECTED-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Ditolak->value,
        'payment_date' => now()->subDay(),
        'rejection_reason' => 'Foto bukti terpotong',
    ]);

    $file = UploadedFile::fake()->create('bukti_baru.jpg', 500, 'image/jpeg');

    // Tenant submits again with clearer proof
    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));
    expect(Payment::where('bill_id', $bill->id)->count())->toBe(2);
    expect(Payment::where('bill_id', $bill->id)->where('status', PaymentStatus::MenungguVerifikasi->value)->exists())->toBeTrue();
});

test('12. a verified successful payment does not block another payment when a balance remains', function () {
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

    // Verified partial payment
    Payment::create([
        'payment_number' => 'PAY-SUCCESS-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Berhasil->value,
        'payment_date' => now()->subDays(2),
        'verified_by' => $this->ownerA->id,
        'verified_at' => now()->subDays(2),
    ]);

    $bill->update([
        'paid_amount' => 1000000,
        'status' => BillStatus::Sebagian->value,
    ]);

    expect($bill->outstandingAmount())->toBe(500000.0);

    $file = UploadedFile::fake()->create('bukti_pelunasan.jpg', 500, 'image/jpeg');

    // Tenant submits second payment for remaining 500.000
    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));
    expect(Payment::where('bill_id', $bill->id)->count())->toBe(2);
    expect(Payment::where('bill_id', $bill->id)->latest('id')->first()->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
});

test('13. submission does not modify bills paid_amount', function () {
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

    $file = UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg');

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    // paid_amount MUST remain 0.00 until verified in Batch 4
    expect((float) $bill->fresh()->paid_amount)->toBe(0.0);
    expect($bill->fresh()->status)->not->toBe(BillStatus::Lunas->value);
});

test('14. spoofed payment status, verifier, or verified timestamp are ignored or rejected', function () {
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

    $file = UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg');

    // Client attempts to spoof payment status as 'berhasil' and verified_by as ownerA
    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => 'berhasil',
        'verified_by' => $this->ownerA->id,
        'verified_at' => now()->toDateTimeString(),
        'proofs' => [$file],
    ]);

    $payment = Payment::where('bill_id', $bill->id)->firstOrFail();
    expect($payment->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
    expect($payment->verified_by)->toBeNull();
    expect($payment->verified_at)->toBeNull();
});

test('15. concurrent submission attempts cannot create multiple pending payments for the same invoice', function () {
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

    $file1 = UploadedFile::fake()->create('bukti1.jpg', 500, 'image/jpeg');
    $file2 = UploadedFile::fake()->create('bukti2.jpg', 500, 'image/jpeg');

    // 1st request acquires lock and creates payment
    $resp1 = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file1],
    ]);
    $resp1->assertRedirect();

    // 2nd simultaneous attempt blocked by row lock & pending payment guard
    $resp2 = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file2],
    ]);
    $resp2->assertSessionHasErrors('amount');

    expect(Payment::where('bill_id', $bill->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Section 3: Proof Security (Tests 16-25)
|--------------------------------------------------------------------------
*/

test('16. JPEG, PNG, and PDF files are accepted when valid', function () {
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

    $jpeg = UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg');
    $png = UploadedFile::fake()->create('bukti.png', 500, 'image/png');
    $pdf = UploadedFile::fake()->create('rekening.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$jpeg, $png, $pdf],
    ]);

    $response->assertSessionHasNoErrors();
    $payment = Payment::where('bill_id', $bill->id)->firstOrFail();
    expect($payment->proofs)->toHaveCount(3);
});

test('17. unsupported file formats are rejected', function () {
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

    $exe = UploadedFile::fake()->create('malware.exe', 500);

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$exe],
    ])->assertSessionHasErrors('proofs.0');
});

test('18. invalid MIME/content combinations are rejected', function () {
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

    // Text file disguised with .jpg extension
    $disguised = UploadedFile::fake()->create('script.jpg', 100, 'text/plain');

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$disguised],
    ])->assertSessionHasErrors();
});

test('19. files are stored on private storage, not public storage', function () {
    Storage::fake('local');
    Storage::fake('public');

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

    $file = UploadedFile::fake()->create('bukti_private.jpg', 500, 'image/jpeg');

    $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'proofs' => [$file],
    ]);

    $proof = PaymentProof::whereHas('payment', fn ($q) => $q->where('bill_id', $bill->id))->firstOrFail();

    // MUST exist on private local disk
    expect(Storage::disk('local')->exists($proof->file_path))->toBeTrue();

    // MUST NOT exist on public disk
    expect(Storage::disk('public')->exists($proof->file_path))->toBeFalse();
});

test('20. failed database persistence cleans up newly uploaded files', function () {
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

    $file = UploadedFile::fake()->create('bukti_cleanup.jpg', 500, 'image/jpeg');

    // Trigger failure during persistence inside the transaction
    PaymentProof::creating(function () {
        throw new \RuntimeException('Database disk write error during persistence');
    });

    try {
        $this->actingAs($this->tenantA)->post(route('tenant.bills.payments.store', $bill), [
            'amount' => 1500000,
            'payment_method' => 'Transfer Bank',
            'proofs' => [$file],
        ]);
    } catch (\Throwable $e) {
        // Exception caught as expected
    }

    // Verify all files on private disk were cleaned up!
    $files = Storage::disk('local')->allFiles('payments');
    expect($files)->toBeEmpty();
});

test('21. the authorized tenant can access their own proof', function () {
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

    $storedPath = Storage::disk('local')->put('payments/bukti_sah.jpg', 'FAKE_IMAGE_BYTES');

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_sah.jpg',
        'file_path' => 'payments/bukti_sah.jpg',
        'file_size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->actingAs($this->tenantA)->get(route('payments.proofs.download', [$payment, $proof]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('bukti_sah.jpg');
});

test('22. the property owner can access the proof', function () {
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

    Storage::disk('local')->put('payments/bukti_owner.jpg', 'FAKE_IMAGE_BYTES');

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_owner.jpg',
        'file_path' => 'payments/bukti_owner.jpg',
        'file_size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    // Owner A (who owns Property A) accesses proof
    $response = $this->actingAs($this->ownerA)->get(route('payments.proofs.download', [$payment, $proof]));

    $response->assertOk();
});

test('23. unrelated tenants and owners cannot access the proof', function () {
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
        'payment_number' => 'PAY-PROOF-003',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    Storage::disk('local')->put('payments/bukti_secure.jpg', 'FAKE_IMAGE_BYTES');

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_secure.jpg',
        'file_path' => 'payments/bukti_secure.jpg',
        'file_size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    // 1. Unrelated Tenant B attempts to download
    $this->actingAs($this->tenantB)
        ->get(route('payments.proofs.download', [$payment, $proof]))
        ->assertForbidden();

    // 2. Unrelated Owner B attempts to download
    $this->actingAs($this->ownerB)
        ->get(route('payments.proofs.download', [$payment, $proof]))
        ->assertForbidden();
});

test('24. private storage paths are never returned as public URLs', function () {
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
        'payment_number' => 'PAY-PROOF-004',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'bukti_penting.jpg',
        'file_path' => 'payments/secret_hash_filename.jpg',
        'file_size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->actingAs($this->tenantA)->get(route('tenant.bills.show', $bill));

    $content = $response->getContent();
    // Must NOT leak relative or physical private storage path
    expect($content)->not->toContain('payments/secret_hash_filename.jpg');
    expect($content)->not->toContain('/storage/payments/');
    expect($content)->not->toContain('storage/app/private');
});

test('25. payment and proof history persists after a rejected attempt', function () {
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

    Storage::disk('local')->put('payments/rejected_proof.jpg', 'REJECTED_CONTENT');

    $payment = Payment::create([
        'payment_number' => 'PAY-HIST-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'status' => PaymentStatus::Ditolak->value,
        'payment_date' => now()->subDay(),
        'rejection_reason' => 'Nominal transfer tidak sesuai',
    ]);

    $proof = PaymentProof::create([
        'payment_id' => $payment->id,
        'file_name' => 'struk_salah.jpg',
        'file_path' => 'payments/rejected_proof.jpg',
        'file_size' => 2048,
        'mime_type' => 'image/jpeg',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Ditolak->value);
    expect($payment->fresh()->rejection_reason)->toBe('Nominal transfer tidak sesuai');
    expect($proof->fresh())->not->toBeNull();
    expect(Storage::disk('local')->exists($proof->file_path))->toBeTrue();
});
