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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->billService = app(BillService::class);

    // Owner A & Property A & Room A1 & Room A2
    $this->ownerA = User::create([
        'name' => 'Owner Alpha',
        'email' => 'owner_alpha_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Graha Alpha '.Str::random(5),
        'slug' => 'kos-graha-alpha-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Anggrek No. 10',
        'city' => 'Jakarta Selatan',
        'district' => 'Kebayoran Baru',
        'postal_code' => '12110',
        'total_rooms' => 10,
        'status' => 'aktif',
    ]);

    $this->propertyA2 = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Wisma Alpha 2 '.Str::random(5),
        'slug' => 'kos-wisma-alpha-2-'.Str::random(8),
        'type' => 'putri',
        'address' => 'Jl. Melati No. 20',
        'city' => 'Jakarta Selatan',
        'district' => 'Cilandak',
        'postal_code' => '12430',
        'total_rooms' => 5,
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

    $this->roomA2 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '102',
        'floor' => 1,
        'type' => 'deluxe',
        'price' => 2000000,
        'status' => 'terisi',
    ]);

    // Owner B & Property B & Room B1 (Unrelated Owner)
    $this->ownerB = User::create([
        'name' => 'Owner Beta',
        'email' => 'owner_beta_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Beta Residence '.Str::random(5),
        'slug' => 'kos-beta-residence-'.Str::random(8),
        'type' => 'putra',
        'address' => 'Jl. Mawar No. 5',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 8,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => 'B01',
        'floor' => 1,
        'type' => 'standar',
        'price' => 1800000,
        'status' => 'terisi',
    ]);

    // Tenants
    $this->tenantA = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi_tenant_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Siti Rahma',
        'email' => 'siti_tenant_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Active Contract for Owner A
    $this->contractA = Contract::create([
        'contract_number' => 'CTR-A-'.Str::random(6),
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

    // Active Contract for Owner B
    $this->contractB = Contract::create([
        'contract_number' => 'CTR-B-'.Str::random(6),
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
| Section 1: Listing and Authorization (Tests 1-5)
|--------------------------------------------------------------------------
*/

test('1. owner can view their own invoices listing', function () {
    $bill = $this->billService->createInvoice([
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

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.bills.index'));

    $response->assertOk()
        ->assertSee($bill->bill_number)
        ->assertSee('Budi Santoso')
        ->assertSee('Kos Graha Alpha');
});

test('2. owner cannot view another owner invoices in listing', function () {
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

    // Owner A views list
    $responseA = $this->actingAs($this->ownerA)->get(route('owner.bills.index'));
    $responseA->assertOk()
        ->assertSee($billA->bill_number)
        ->assertDontSee($billB->bill_number);

    // Owner B views list
    $responseB = $this->actingAs($this->ownerB)->get(route('owner.bills.index'));
    $responseB->assertOk()
        ->assertSee($billB->bill_number)
        ->assertDontSee($billA->bill_number);
});

test('3. search and filters work together in invoice listing', function () {
    // Bill 1: Property A, 2026-10, type sewa, status menunggu_pembayaran
    $bill1 = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Okt', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // Bill 2: Property A, 2026-10, type tambahan
    $bill2 = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-02',
        'due_date' => '2026-10-09',
    ], [
        ['description' => 'Laundry Express', 'amount' => 50000, 'quantity' => 2],
    ]);

    // 1. Filter by type 'tambahan'
    $response = $this->actingAs($this->ownerA)->get(route('owner.bills.index', [
        'type' => BillType::Tambahan->value,
    ]));
    $response->assertOk()
        ->assertSee($bill2->bill_number)
        ->assertDontSee($bill1->bill_number);

    // 2. Filter by search query 'Laundry' or bill_number
    $response = $this->actingAs($this->ownerA)->get(route('owner.bills.index', [
        'q' => $bill1->bill_number,
    ]));
    $response->assertOk()
        ->assertSee($bill1->bill_number)
        ->assertDontSee($bill2->bill_number);

    // 3. Filter by property
    $response = $this->actingAs($this->ownerA)->get(route('owner.bills.index', [
        'property_id' => $this->propertyA2->id,
    ]));
    $response->assertOk()
        ->assertDontSee($bill1->bill_number)
        ->assertDontSee($bill2->bill_number);
});

test('4. pagination preserves query filters', function () {
    // Generate 12 bills to trigger pagination (10 per page)
    for ($i = 1; $i <= 12; $i++) {
        $this->billService->createInvoice([
            'contract_id' => $this->contractA->id,
            'user_id' => $this->tenantA->id,
            'room_id' => $this->roomA1->id,
            'type' => BillType::Tambahan->value,
            'billing_period' => '2026-10',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-08',
            'notes' => "Invoice Item Batch #{$i}",
        ], [
            ['description' => "Item #{$i}", 'amount' => 10000 * $i, 'quantity' => 1],
        ]);
    }

    $response = $this->actingAs($this->ownerA)->get(route('owner.bills.index', [
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
    ]));

    $response->assertOk();
    // Verify query parameters are preserved in pagination links
    $content = $response->getContent();
    expect($content)->toContain('type=tambahan');
    expect($content)->toContain('billing_period=2026-10');
});

test('5. tenant cannot access owner invoice management routes', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // 1. Tenant index access
    $this->actingAs($this->tenantA)
        ->get(route('owner.bills.index'))
        ->assertForbidden();

    // 2. Tenant create access
    $this->actingAs($this->tenantA)
        ->get(route('owner.bills.create'))
        ->assertForbidden();

    // 3. Tenant store access
    $this->actingAs($this->tenantA)
        ->post(route('owner.bills.store'), [
            'contract_id' => $this->contractA->id,
            'type' => BillType::Tambahan->value,
            'issue_date' => '2026-10-01',
            'items' => [
                ['description' => 'Test', 'amount' => 10000, 'quantity' => 1],
            ],
        ])
        ->assertForbidden();

    // 4. Tenant cancel access
    $this->actingAs($this->tenantA)
        ->post(route('owner.bills.cancel', $bill), [
            'cancellation_reason' => 'Tenant trying to cancel',
        ])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Section 2: Manual Invoice Creation (Tests 6-14)
|--------------------------------------------------------------------------
*/

test('6. owner can create an additional invoice with multiple line items', function () {
    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-15',
        'due_date' => '2026-10-22',
        'notes' => 'Tagihan perbaikan keran & penggantian lampu kamar',
        'items' => [
            ['description' => 'Jasa Perbaikan Keran', 'amount' => 75000, 'quantity' => 1],
            ['description' => 'Lampu LED 15W', 'amount' => 45000, 'quantity' => 2],
        ],
    ]);

    $bill = Bill::where('contract_id', $this->contractA->id)
        ->where('type', BillType::Tambahan->value)
        ->latest('id')
        ->first();

    expect($bill)->not->toBeNull();
    $response->assertRedirect(route('owner.bills.show', $bill));

    expect($bill->items)->toHaveCount(2);
    expect((float) $bill->total_amount)->toBe(165000.0); // 75000 + (45000 * 2) = 165000
    expect((float) $bill->paid_amount)->toBe(0.0);
    expect($bill->status)->toBe(BillStatus::MenungguPembayaran->value);
});

test('7. the server calculates the invoice total correctly', function () {
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'items' => [
            ['description' => 'Item A', 'amount' => 125000.50, 'quantity' => 3], // 375001.50
            ['description' => 'Item B', 'amount' => 24999.50, 'quantity' => 2],  // 49999.00
        ],
    ]);

    $bill = Bill::where('contract_id', $this->contractA->id)
        ->where('type', BillType::Tambahan->value)
        ->latest('id')
        ->firstOrFail();

    // 375001.50 + 49999.00 = 425000.50
    expect((float) $bill->total_amount)->toBe(425000.50);
});

test('8. spoofed client totals are ignored or rejected', function () {
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'total_amount' => 999, // Spoofed amount!
        'paid_amount' => 5000000, // Spoofed paid amount!
        'items' => [
            ['description' => 'Item Asli', 'amount' => 200000, 'quantity' => 1],
        ],
    ]);

    $bill = Bill::where('contract_id', $this->contractA->id)
        ->where('type', BillType::Tambahan->value)
        ->latest('id')
        ->firstOrFail();

    // Must be 200000, never 999 or paid 5000000
    expect((float) $bill->total_amount)->toBe(200000.0);
    expect((float) $bill->paid_amount)->toBe(0.0);
});

test('9. foreign-owner contract IDs are rejected', function () {
    // Owner A attempts to bill Owner B's contract
    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractB->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'items' => [
            ['description' => 'Item Ilegal', 'amount' => 50000, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('contract_id');
    expect(Bill::where('contract_id', $this->contractB->id)->where('total_amount', 50000)->exists())->toBeFalse();
});

test('10. duplicate rent invoices are prevented', function () {
    // Existing rent bill for period 2026-11
    $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'deduplication_key' => "rent:{$this->contractA->id}:2026-11",
        'billing_period' => '2026-11',
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-08',
    ], [
        ['description' => 'Sewa Nov', 'amount' => 1500000, 'quantity' => 1],
    ]);

    // Attempt to manually create duplicate rent invoice for same period
    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-11',
        'issue_date' => '2026-11-01',
        'items' => [
            ['description' => 'Sewa Nov Duplikat', 'amount' => 1500000, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('billing_period');
});

test('11. duplicate deposit invoices are prevented', function () {
    // Existing deposit bill
    $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Deposit->value,
        'deduplication_key' => "deposit:{$this->contractA->id}",
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Uang Jaminan', 'amount' => 500000, 'quantity' => 1],
    ]);

    // Attempt to create another deposit bill
    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Deposit->value,
        'issue_date' => '2026-10-01',
        'items' => [
            ['description' => 'Uang Jaminan Duplikat', 'amount' => 500000, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('type');
});

test('12. multiple additional invoices for the same contract and period are allowed', function () {
    // 1st additional invoice for 2026-10
    $resp1 = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-05',
        'items' => [
            ['description' => 'Laundry', 'amount' => 30000, 'quantity' => 1],
        ],
    ]);
    $resp1->assertSessionHasNoErrors();

    // 2nd additional invoice for same contract and same period 2026-10
    $resp2 = $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-10',
        'items' => [
            ['description' => 'Katering', 'amount' => 150000, 'quantity' => 1],
        ],
    ]);
    $resp2->assertSessionHasNoErrors();

    $additionalBills = Bill::where('contract_id', $this->contractA->id)
        ->where('type', BillType::Tambahan->value)
        ->where('billing_period', '2026-10')
        ->get();

    expect($additionalBills)->toHaveCount(2);
});

test('13. invalid quantities, negative amounts, and invalid dates are rejected', function () {
    // 1. Zero quantity
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'items' => [
            ['description' => 'Item Bad Qty', 'amount' => 50000, 'quantity' => 0],
        ],
    ])->assertSessionHasErrors('items.0.quantity');

    // 2. Negative amount
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'items' => [
            ['description' => 'Item Bad Amount', 'amount' => -10000, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('items.0.amount');

    // 3. Due date earlier than issue date
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-15',
        'due_date' => '2026-10-10', // earlier!
        'items' => [
            ['description' => 'Item Bad Date', 'amount' => 50000, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('due_date');

    // 4. Invalid billing period format
    $this->actingAs($this->ownerA)->post(route('owner.bills.store'), [
        'contract_id' => $this->contractA->id,
        'type' => BillType::Tambahan->value,
        'billing_period' => '2026/10', // Invalid regex!
        'issue_date' => '2026-10-15',
        'items' => [
            ['description' => 'Item Bad Period', 'amount' => 50000, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('billing_period');
});

test('14. database failure rolls back invoice and line-item creation', function () {
    $initialBillCount = Bill::count();
    $initialItemCount = BillItem::count();

    // Trigger atomic rollback by passing invalid item data inside BillService or database error
    try {
        $this->billService->createInvoice([
            'contract_id' => $this->contractA->id,
            'user_id' => $this->tenantA->id,
            'room_id' => $this->roomA1->id,
            'type' => BillType::Tambahan->value,
            'issue_date' => '2026-10-15',
            'due_date' => '2026-10-22',
        ], [
            ['description' => 'Valid Item 1', 'amount' => 50000, 'quantity' => 1],
            ['description' => '', 'amount' => -500, 'quantity' => 0], // Triggers exception!
        ]);
    } catch (\Throwable $e) {
        // Expected exception
    }

    expect(Bill::count())->toBe($initialBillCount);
    expect(BillItem::count())->toBe($initialItemCount);
});

/*
|--------------------------------------------------------------------------
| Section 3: Detail and Cancellation (Tests 15-22)
|--------------------------------------------------------------------------
*/

test('15. owner can view their own invoice detail', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Sewa Kamar 101 Oktober 2026', 'amount' => 1500000, 'quantity' => 1],
    ]);

    $response = $this->actingAs($this->ownerA)->get(route('owner.bills.show', $bill));

    $response->assertOk()
        ->assertSee($bill->bill_number)
        ->assertSee('Sewa Kamar 101 Oktober 2026')
        ->assertSee('1.500.000')
        ->assertSee('Budi Santoso')
        ->assertSee($this->contractA->contract_number);
});

test('16. cross-owner invoice access is forbidden', function () {
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

    // Owner B attempts to view Owner A's invoice
    $response = $this->actingAs($this->ownerB)->get(route('owner.bills.show', $billA));
    $response->assertForbidden();
});

test('17. cancellation requires a valid reason', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Item Tambahan', 'amount' => 50000, 'quantity' => 1],
    ]);

    // 1. Empty reason
    $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => '',
    ])->assertSessionHasErrors('cancellation_reason');

    // 2. Too short (< 5 chars)
    $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'abc',
    ])->assertSessionHasErrors('cancellation_reason');

    expect($bill->fresh()->status)->toBe(BillStatus::MenungguPembayaran->value);
});

test('18. paid invoices cannot be cancelled through the ordinary action', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Item Tambahan', 'amount' => 50000, 'quantity' => 1],
    ]);

    // Mark as paid
    $bill->update([
        'status' => BillStatus::Lunas->value,
        'paid_amount' => 50000,
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Alasan pembatalan yang cukup panjang',
    ]);

    // Policy denies or validation exception
    expect($response->status())->toBeIn([403, 422, 302]);
    expect($bill->fresh()->status)->toBe(BillStatus::Lunas->value);
});

test('19. invoices with successful payments cannot be cancelled through ordinary action', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Item Tambahan', 'amount' => 100000, 'quantity' => 1],
    ]);

    // Add a verified successful payment (e.g. partial payment)
    Payment::create([
        'payment_number' => 'PAY-TEST-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 50000,
        'status' => PaymentStatus::Berhasil->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Membatalkan invoice yang sudah ada pembayaran',
    ]);

    expect($response->status())->toBeIn([403, 422, 302]);
    expect($bill->fresh()->status)->not->toBe(BillStatus::Dibatalkan->value);
});

test('20. pending payments block invoice cancellation', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Item Tambahan', 'amount' => 100000, 'quantity' => 1],
    ]);

    // Add a payment awaiting verification
    Payment::create([
        'payment_number' => 'PAY-TEST-002',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 100000,
        'status' => PaymentStatus::MenungguVerifikasi->value,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Mencoba membatalkan saat ada bukti bayar pending',
    ]);

    expect($response->status())->toBeIn([403, 422, 302]);
    expect($bill->fresh()->status)->not->toBe(BillStatus::Dibatalkan->value);
});

test('21. cancellation preserves payment history and invoice items', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
        'notes' => 'Catatan awal tagihan.',
    ], [
        ['description' => 'Rincian Item 1', 'amount' => 70000, 'quantity' => 1],
        ['description' => 'Rincian Item 2', 'amount' => 30000, 'quantity' => 1],
    ]);

    // Historical rejected payment
    $payment = Payment::create([
        'payment_number' => 'PAY-TEST-REJECTED',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantA->id,
        'amount' => 100000,
        'status' => PaymentStatus::Ditolak->value,
        'payment_date' => now(),
        'rejection_reason' => 'Bukti transfer buram',
    ]);

    $response = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Penyewa sepakat membatalkan perbaikan ini.',
    ]);

    $response->assertRedirect(route('owner.bills.show', $bill));

    $freshBill = $bill->fresh();
    expect($freshBill->status)->toBe(BillStatus::Dibatalkan->value);
    expect($freshBill->notes)->toContain('Catatan awal tagihan.');
    expect($freshBill->notes)->toContain('Penyewa sepakat membatalkan perbaikan ini.');
    expect($freshBill->notes)->toContain('oleh Pemilik');

    // Financial records and items preserved
    expect($freshBill->items)->toHaveCount(2);
    expect($freshBill->payments)->toHaveCount(1);
    expect($freshBill->payments->first()->id)->toBe($payment->id);
    expect((float) $freshBill->total_amount)->toBe(100000.0);
});

test('22. repeated cancellation is handled safely', function () {
    $bill = $this->billService->createInvoice([
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenantA->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Tambahan->value,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-08',
    ], [
        ['description' => 'Item Tambahan', 'amount' => 50000, 'quantity' => 1],
    ]);

    // 1st cancellation
    $resp1 = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Pembatalan pertama yang sah.',
    ]);
    $resp1->assertRedirect();
    expect($bill->fresh()->status)->toBe(BillStatus::Dibatalkan->value);

    // 2nd cancellation attempt
    $resp2 = $this->actingAs($this->ownerA)->post(route('owner.bills.cancel', $bill), [
        'cancellation_reason' => 'Pembatalan kedua yang tidak perlu.',
    ]);

    // Safe handling: forbidden or idempotent redirect without data corruption
    expect($resp2->status())->toBeIn([403, 302]);
    expect($bill->fresh()->status)->toBe(BillStatus::Dibatalkan->value);
});

/*
|--------------------------------------------------------------------------
| Section 4: Regression and Recurring Command (Tests 23-25)
|--------------------------------------------------------------------------
*/

test('24. one recurring-generation failure is logged and does not stop processing other contracts', function () {
    // Contract 1: normal valid contract
    $contract1 = $this->contractA;

    // Contract 2: second valid contract under Owner A
    $contract2 = Contract::create([
        'contract_number' => 'CTR-A-SEC-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA2->id,
        'user_id' => $this->tenantB->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    // Mock BillService to throw on contract 1, but succeed on contract 2
    $mockBillService = \Mockery::mock(BillService::class)->makePartial();
    $mockBillService->shouldReceive('normalizePeriod')->andReturn('2026-12');
    $mockBillService->shouldReceive('formatPeriod')->andReturn('Desember 2026');

    $mockBillService->shouldReceive('generateRecurringRentInvoice')
        ->with(\Mockery::on(fn ($c) => $c->id === $contract1->id), '2026-12')
        ->andThrow(new \RuntimeException('Simulated failure on contract 1'));

    $realBillService = app(BillService::class);
    $mockBillService->shouldReceive('generateRecurringRentInvoice')
        ->with(\Mockery::on(fn ($c) => $c->id === $contract2->id), '2026-12')
        ->andReturnUsing(function ($contract, $period) use ($realBillService) {
            return $realBillService->generateRecurringRentInvoice($contract, $period);
        });

    app()->instance(BillService::class, $mockBillService);

    Log::shouldReceive('error')
        ->once()
        ->with(\Mockery::pattern('/Gagal membuat tagihan sewa berulang untuk kontrak/'), \Mockery::on(function ($context) use ($contract1) {
            return isset($context['contract_id']) && $context['contract_id'] === $contract1->id
                && isset($context['billing_period']) && $context['billing_period'] === '2026-12';
        }));

    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-12',
    ])->assertFailed();

    // Contract 2 should have succeeded despite contract 1 failing!
    $billContract2 = Bill::where('contract_id', $contract2->id)->where('billing_period', '2026-12')->first();
    expect($billContract2)->not->toBeNull();
    expect((float) $billContract2->total_amount)->toBe(2000000.0);
});

test('25. re-running the recurring command does not duplicate invoices', function () {
    // Run recurring command for period 2027-01
    $this->artisan('bills:generate-recurring', [
        '--period' => '2027-01',
    ])->assertSuccessful();

    $countAfterFirstRun = Bill::where('billing_period', '2027-01')->count();
    expect($countAfterFirstRun)->toBeGreaterThanOrEqual(1);

    // Run again for the exact same period
    $this->artisan('bills:generate-recurring', [
        '--period' => '2027-01',
    ])->assertSuccessful();

    $countAfterSecondRun = Bill::where('billing_period', '2027-01')->count();
    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});
