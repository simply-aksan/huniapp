<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\BillService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

beforeEach(function () {
    $this->billService = app(BillService::class);

    // Owner A & Property A
    $this->ownerA = User::create([
        'name' => 'Owner Financial A',
        'email' => 'owner_fin_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Property Fin A '.Str::random(5),
        'slug' => 'prop-fin-a-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Keuangan No. 1',
        'city' => 'Jakarta Selatan',
        'district' => 'Kebayoran',
        'postal_code' => '12110',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '101',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'kosong',
    ]);

    // Owner B & Property B (Unrelated Owner)
    $this->ownerB = User::create([
        'name' => 'Owner Financial B',
        'email' => 'owner_fin_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Property Fin B '.Str::random(5),
        'slug' => 'prop-fin-b-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Keuangan No. 2',
        'city' => 'Jakarta Selatan',
        'district' => 'Kebayoran',
        'postal_code' => '12110',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => '201',
        'floor' => 2,
        'type' => 'standar',
        'price' => 2500000,
        'status' => 'kosong',
    ]);

    // Tenants
    $this->tenantA = User::create([
        'name' => 'Tenant Financial A',
        'email' => 'tenant_fin_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Tenant Financial B',
        'email' => 'tenant_fin_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Section 1: Invoice Numbering & Uniqueness
|--------------------------------------------------------------------------
*/

test('1. official invoice number format matches INV-YYYYMM-XXXX', function () {
    $now = Carbon::create(2026, 10, 14);
    $number = $this->billService->generateInvoiceNumber($now);

    expect($number)->toMatch('/^INV-202610-\d{4}$/');
});

test('2. sequential allocation increments and preserves uniqueness', function () {
    $date = Carbon::create(2026, 10, 1);
    $num1 = $this->billService->generateInvoiceNumber($date);

    // Create a bill with num1
    Bill::create([
        'bill_number' => $num1,
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
        'total_amount' => 1000000,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $num2 = $this->billService->generateInvoiceNumber($date);

    expect($num1)->not->toBe($num2);
    expect(substr($num2, -4))->toBe(sprintf('%04d', ((int) substr($num1, -4)) + 1));
});

test('3. retry mechanism resolves concurrent duplicate invoice number collisions', function () {
    $date = Carbon::create(2026, 10, 5);
    $targetNumber = $this->billService->generateInvoiceNumber($date);

    // Create bill with targetNumber to simulate another process taking it right before insertion
    Bill::create([
        'bill_number' => $targetNumber,
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-05',
        'due_date' => '2026-10-12',
        'total_amount' => 500000,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // createInvoice must retry and assign next available sequence number
    $bill = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
        'issue_date' => $date,
    ], [
        ['description' => 'Test Item', 'amount' => 500000, 'quantity' => 1],
    ]);

    expect($bill->bill_number)->not->toBe($targetNumber)
        ->and(substr($bill->bill_number, -4))->toBe(sprintf('%04d', ((int) substr($targetNumber, -4)) + 1));
});

test('4. recurring invoice duplicate prevention prevents duplicate monthly rent bills', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'aktif',
    ]);

    $bill1 = $this->billService->generateRecurringRentInvoice($contract, '2026-10');
    $bill2 = $this->billService->generateRecurringRentInvoice($contract, '2026-10');

    expect($bill1)->not->toBeNull()
        ->and($bill2)->not->toBeNull()
        ->and($bill1->id)->toBe($bill2->id);

    // Exactly one rent bill exists for that contract and month
    expect(Bill::where('contract_id', $contract->id)->where('billing_period', '2026-10')->where('type', 'sewa')->count())->toBe(1);
});

test('5. deposit and additional invoices do not conflict with monthly rent invoices in same period', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'deposit_amount' => 1000000,
        'status' => 'aktif',
    ]);

    // 1. Initial invoices: creates Rent AND Deposit for 2026-10
    $initial = $this->billService->generateInitialInvoices($contract);
    expect($initial['rent'])->not->toBeNull()
        ->and($initial['deposit'])->not->toBeNull()
        ->and($initial['rent']->type)->toBe(BillType::Sewa->value)
        ->and($initial['deposit']->type)->toBe(BillType::Deposit->value)
        ->and($initial['rent']->billing_period)->toBe('2026-10')
        ->and($initial['deposit']->billing_period)->toBe('2026-10');

    // 2. Additional invoices in 2026-10
    $add1 = $this->billService->generateAdditionalInvoice($contract, 'Denda Kunci', 50000, '2026-10');
    $add2 = $this->billService->generateAdditionalInvoice($contract, 'Listrik Tambahan', 120000, '2026-10');

    expect($add1->id)->not->toBe($add2->id)
        ->and($add1->type)->toBe(BillType::Tambahan->value)
        ->and($add2->type)->toBe(BillType::Tambahan->value);

    // Total 4 distinct bills for contract in 2026-10 without any constraint violation
    expect(Bill::where('contract_id', $contract->id)->where('billing_period', '2026-10')->count())->toBe(4);
});

/*
|--------------------------------------------------------------------------
| Section 2: Financial Calculations
|--------------------------------------------------------------------------
*/

test('6. server calculates correct sum of multiple line items', function () {
    $bill = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Sewa Kamar', 'amount' => 2000000, 'quantity' => 1],
        ['description' => 'Biaya Sampah', 'amount' => 25000, 'quantity' => 1],
        ['description' => 'Laundry', 'amount' => 50000, 'quantity' => 2],
    ]);

    expect((float) $bill->total_amount)->toBe(2125000.0)
        ->and($bill->items->count())->toBe(3)
        ->and($bill->items->sum(fn ($i) => $i->total()))->toBe(2125000.0);
});

test('7. server-side total calculation ignores client-supplied totals', function () {
    $bill = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
        'total_amount' => 999, // spoofed client value
    ], [
        ['description' => 'Item A', 'amount' => 1500000, 'quantity' => 1],
    ]);

    expect((float) $bill->total_amount)->toBe(1500000.0);
});

test('8. correct seven-day due-date calculation', function () {
    $issue = Carbon::create(2026, 10, 10);
    $due = $this->billService->calculateDueDate($issue);

    expect($due->toDateString())->toBe('2026-10-17')
        ->and((int) $issue->diffInDays($due))->toBe(7);
});

test('9. correct proration for a partial first month', function () {
    // October has 31 days. Start October 15 to October 31 = 17 days.
    // Monthly rent 3,100,000 -> 3,100,000 / 31 * 17 = 1,700,000.00
    $rent = $this->billService->calculateProratedRent(3100000, '2026-10-15', '2027-01-14', '2026-10');

    expect($rent)->toBe(1700000.0);
});

test('10. correct proration for a partial final month', function () {
    // November has 30 days. Contract ends on November 10 (Nov 1 to Nov 10 = 10 days).
    // Monthly rent 3,000,000 -> 3,000,000 / 30 * 10 = 1,000,000.00
    $rent = $this->billService->calculateProratedRent(3000000, '2026-08-01', '2026-11-10', '2026-11');

    expect($rent)->toBe(1000000.0);
});

test('11. leap-year and month-length edge cases calculate accurately', function () {
    // February 2028 is a leap year (29 days).
    // Start Feb 15 to Feb 29 = 15 days out of 29 days.
    // Rent = 2,900,000 / 29 * 15 = 1,500,000.00
    $rentLeap = $this->billService->calculateProratedRent(2900000, '2028-02-15', '2028-05-31', '2028-02');
    expect($rentLeap)->toBe(1500000.0);

    // February 2027 is a standard year (28 days).
    // Start Feb 15 to Feb 28 = 14 days out of 28 days = exactly half month.
    // Rent = 2,800,000 / 28 * 14 = 1,400,000.00
    $rentStd = $this->billService->calculateProratedRent(2800000, '2027-02-15', '2027-05-31', '2027-02');
    expect($rentStd)->toBe(1400000.0);
});

test('12. full calendar months use full monthly rent without rounding artifacts', function () {
    // Contract starts 2026-10-01 and ends 2026-12-31.
    // Period 2026-10 (31 days, starts 1st, ends > 31st) must be exactly monthly rent.
    $rent = $this->billService->calculateProratedRent(2000000, '2026-10-01', '2026-12-31', '2026-10');

    expect($rent)->toBe(2000000.0);
});

test('13. invalid or negative item amounts are rejected', function () {
    expect(function () {
        $this->billService->createInvoice([
            'user_id' => $this->tenantA->id,
            'billing_period' => '2026-10',
        ], [
            ['description' => 'Negatif', 'amount' => -50000, 'quantity' => 1],
        ]);
    })->toThrow(InvalidArgumentException::class);

    expect(function () {
        $this->billService->createInvoice([
            'user_id' => $this->tenantA->id,
            'billing_period' => '2026-10',
        ], [
            ['description' => 'Nol', 'amount' => 0, 'quantity' => 1],
        ]);
    })->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Section 3: Contract Lifecycle Integration
|--------------------------------------------------------------------------
*/

test('14. draft contracts do not trigger invoice creation', function () {
    $draft = Contract::create([
        'contract_number' => 'CTR-DRAFT-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'deposit_amount' => 1000000,
        'status' => 'draft',
    ]);

    $invoices = $this->billService->generateInitialInvoices($draft);

    expect($invoices['rent'])->toBeNull()
        ->and($invoices['deposit'])->toBeNull()
        ->and(Bill::where('contract_id', $draft->id)->count())->toBe(0);
});

test('15. contract activation creates initial rent and separate deposit invoices once', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-ACTIVE-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'deposit_amount' => 1000000,
        'status' => 'aktif',
    ]);

    $invoices = $this->billService->generateInitialInvoices($contract);

    expect($invoices['rent'])->not->toBeNull()
        ->and((float) $invoices['rent']->total_amount)->toBe(2000000.0)
        ->and($invoices['rent']->type)->toBe('sewa')
        ->and($invoices['deposit'])->not->toBeNull()
        ->and((float) $invoices['deposit']->total_amount)->toBe(1000000.0)
        ->and($invoices['deposit']->type)->toBe('deposit');
});

test('16. repeated activation handling is idempotent and does not duplicate invoices', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-REPEAT-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'deposit_amount' => 1000000,
        'status' => 'aktif',
    ]);

    $firstCall = $this->billService->generateInitialInvoices($contract);
    $secondCall = $this->billService->generateInitialInvoices($contract);

    expect($secondCall['rent']->id)->toBe($firstCall['rent']->id)
        ->and($secondCall['deposit']->id)->toBe($firstCall['deposit']->id)
        ->and(Bill::where('contract_id', $contract->id)->count())->toBe(2);
});

test('17. cancelled and completed contracts do not receive future recurring invoices', function () {
    $completed = Contract::create([
        'contract_number' => 'CTR-DONE-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-09-30',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'selesai',
    ]);

    $cancelled = Contract::create([
        'contract_number' => 'CTR-CANCEL-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-09-30',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'dibatalkan',
    ]);

    expect($this->billService->generateRecurringRentInvoice($completed, '2026-10'))->toBeNull()
        ->and($this->billService->generateRecurringRentInvoice($cancelled, '2026-10'))->toBeNull();
});

test('18. historical contracts may support valid additional charges', function () {
    $completed = Contract::create([
        'contract_number' => 'CTR-HIST-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-09-30',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'selesai',
    ]);

    $additional = $this->billService->generateAdditionalInvoice(
        $completed,
        'Denda Kerusakan Cat Dinding',
        350000,
        '2026-10',
        'Pelunasan biaya perbaikan kamar pasca sewa selesai'
    );

    expect($additional)->not->toBeNull()
        ->and($additional->contract_id)->toBe($completed->id)
        ->and((float) $additional->total_amount)->toBe(350000.0)
        ->and($additional->type)->toBe('tambahan');
});

/*
|--------------------------------------------------------------------------
| Section 4: Integrity and Authorization
|--------------------------------------------------------------------------
*/

test('19. owners cannot view, update, or cancel bills for unrelated properties', function () {
    $billA = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Item', 'amount' => 1000000, 'quantity' => 1],
    ]);

    // Owner A owns property of roomA1
    expect(Gate::forUser($this->ownerA)->allows('view', $billA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('update', $billA))->toBeTrue()
        ->and(Gate::forUser($this->ownerA)->allows('cancel', $billA))->toBeTrue();

    // Owner B must be strictly denied
    expect(Gate::forUser($this->ownerB)->allows('view', $billA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('update', $billA))->toBeFalse()
        ->and(Gate::forUser($this->ownerB)->allows('cancel', $billA))->toBeFalse();
});

test('20. tenants cannot access owner invoice-management operations', function () {
    $billA = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Item', 'amount' => 1000000, 'quantity' => 1],
    ]);

    // Tenant A is the recipient
    expect(Gate::forUser($this->tenantA)->allows('view', $billA))->toBeTrue()
        ->and(Gate::forUser($this->tenantA)->allows('create', Bill::class))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('update', $billA))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('delete', $billA))->toBeFalse()
        ->and(Gate::forUser($this->tenantA)->allows('cancel', $billA))->toBeFalse();

    // Tenant B cannot even view Tenant A's bill
    expect(Gate::forUser($this->tenantB)->allows('view', $billA))->toBeFalse();
});

test('21. contracts with bills cannot be hard-deleted', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-DEL-GUARD-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'aktif',
    ]);

    // Bill issued under this contract
    $this->billService->createInvoice([
        'contract_id' => $contract->id,
        'user_id' => $this->tenantA->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Sewa', 'amount' => 2000000, 'quantity' => 1],
    ]);

    // Policy must deny deletion
    expect(Gate::forUser($this->ownerA)->allows('delete', $contract))->toBeFalse();

    // Attempting delete via controller must be rejected with 403 Forbidden
    $response = $this->actingAs($this->ownerA)->delete(route('owner.contracts.destroy', $contract));
    $response->assertForbidden();
    expect(Contract::where('id', $contract->id)->exists())->toBeTrue();
});

test('22. bills with payments cannot be hard-deleted', function () {
    $bill = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Sewa', 'amount' => 2000000, 'quantity' => 1],
    ]);

    // Record a payment against this bill
    Payment::create([
        'payment_number' => 'PAY-TEST-'.Str::random(5),
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    // Policy must deny deletion of bill
    expect(Gate::forUser($this->ownerA)->allows('delete', $bill))->toBeFalse();
});

test('23. invoice and line-item creation rolls back atomically when a failure occurs', function () {
    $initialBillCount = Bill::count();
    $initialItemCount = BillItem::count();

    expect(function () {
        $this->billService->createInvoice([
            'user_id' => $this->tenantA->id,
            'billing_period' => '2026-10',
        ], [
            ['description' => 'Item 1', 'amount' => 1000000, 'quantity' => 1],
            ['description' => 'Item Invalid', 'amount' => -200000, 'quantity' => 1],
        ]);
    })->toThrow(InvalidArgumentException::class);

    // Database must not have orphaned bills or items
    expect(Bill::count())->toBe($initialBillCount)
        ->and(BillItem::count())->toBe($initialItemCount);
});

test('24. pending payment rule properly gates payment acceptance', function () {
    $bill = $this->billService->createInvoice([
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => '2026-10',
    ], [
        ['description' => 'Sewa', 'amount' => 2000000, 'quantity' => 1],
    ]);

    expect($bill->hasPendingPayment())->toBeFalse()
        ->and($bill->canAcceptPayment())->toBeTrue()
        ->and($bill->outstandingAmount())->toBe(2000000.0);

    // Add pending payment
    $payment = Payment::create([
        'payment_number' => 'PAY-PENDING-'.Str::random(5),
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    $bill->refresh();
    expect($bill->hasPendingPayment())->toBeTrue()
        ->and($bill->canAcceptPayment())->toBeFalse();

    // When payment is rejected, bill can accept payment again
    $payment->update(['status' => PaymentStatus::Ditolak->value]);
    $bill->refresh();
    expect($bill->hasPendingPayment())->toBeFalse()
        ->and($bill->canAcceptPayment())->toBeTrue();
});

test('25. bills:generate-recurring artisan command generates recurring bills idempotently', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-CMD-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'duration_months' => 3,
        'monthly_rent' => 2000000,
        'total_amount' => 6000000,
        'status' => 'aktif',
    ]);

    // Run command for 2026-11
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $contract->id,
    ])->assertSuccessful();

    expect(Bill::where('contract_id', $contract->id)->where('billing_period', '2026-11')->count())->toBe(1);

    // Re-run command - must be idempotent and skip
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $contract->id,
    ])->assertSuccessful();

    expect(Bill::where('contract_id', $contract->id)->where('billing_period', '2026-11')->count())->toBe(1);
});
