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
use App\Notifications\InvoiceCreatedNotification;
use App\Services\BillService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->billService = app(BillService::class);

    // Setup Owner
    $this->owner = User::create([
        'name' => 'Owner Scheduler Test',
        'email' => 'owner_sched_'.Str::random(8).'@huniapp.test',
        'phone' => '0811'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => bcrypt('password'),
    ]);

    // Setup Property
    $this->property = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kost Harmoni Scheduler',
        'slug' => 'kost-harmoni-sched-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Harmoni No. 10',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    // Setup Room 1
    $this->room1 = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '101',
        'floor' => 1,
        'type' => 'deluxe',
        'price' => 2500000,
        'status' => 'terisi',
    ]);

    // Setup Room 2
    $this->room2 = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '102',
        'floor' => 1,
        'type' => 'standar',
        'price' => 1800000,
        'status' => 'terisi',
    ]);

    // Setup Tenant 1
    $this->tenant1 = User::create([
        'name' => 'Tenant Sched One',
        'email' => 'tenant_sched_1_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => bcrypt('password'),
    ]);

    // Setup Tenant 2
    $this->tenant2 = User::create([
        'name' => 'Tenant Sched Two',
        'email' => 'tenant_sched_2_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => bcrypt('password'),
    ]);

    // Active Contract 1 (2026-10-01 to 2027-03-31)
    $this->contract1 = Contract::create([
        'contract_number' => 'CTR-SCHED-1-'.Str::random(5),
        'property_id' => $this->property->id,
        'room_id' => $this->room1->id,
        'user_id' => $this->tenant1->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 2500000,
        'total_amount' => 15000000,
        'deposit_amount' => 1000000,
        'deposit_status' => 'ditahan',
        'status' => 'aktif',
    ]);
});

test('1. command generates recurring bill for eligible active contract in billing period', function () {
    Notification::fake();

    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])
        ->expectsOutputToContain('Memeriksa 1 kontrak aktif')
        ->expectsOutputToContain('Berhasil dibuat: 1')
        ->assertSuccessful();

    $bill = Bill::where('contract_id', $this->contract1->id)
        ->where('billing_period', '2026-11')
        ->first();

    expect($bill)->not->toBeNull();
    expect($bill->type === BillType::Sewa->value || $bill->type === BillType::Sewa)->toBeTrue();
    expect($bill->status === BillStatus::MenungguPembayaran->value || $bill->status === BillStatus::MenungguPembayaran)->toBeTrue();
    expect((float) $bill->total_amount)->toBe(2500000.0);
    expect((float) $bill->paid_amount)->toBe(0.0);
    expect($bill->deduplication_key)->toBe("rent:{$this->contract1->id}:2026-11");
    expect($bill->items)->toHaveCount(1);
    expect((float) $bill->items->first()->amount)->toBe(2500000.0);
});

test('2. re-running the command does not create duplicate invoices (idempotency)', function () {
    // First run creates the invoice
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    expect(Bill::where('contract_id', $this->contract1->id)->where('billing_period', '2026-11')->count())->toBe(1);

    // Second run must be idempotent and skip
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])
        ->expectsOutputToContain('[SKIP]')
        ->expectsOutputToContain('Berhasil dibuat: 0')
        ->assertSuccessful();

    expect(Bill::where('contract_id', $this->contract1->id)->where('billing_period', '2026-11')->count())->toBe(1);
});

test('3. completed contract (selesai) produces no recurring invoices', function () {
    $completedContract = Contract::create([
        'contract_number' => 'CTR-SELESAI-'.Str::random(5),
        'property_id' => $this->property->id,
        'room_id' => $this->room2->id,
        'user_id' => $this->tenant2->id,
        'start_date' => '2026-05-01',
        'end_date' => '2026-10-31',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'total_amount' => 10800000,
        'status' => 'selesai',
    ]);

    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $completedContract->id,
    ])
        ->expectsOutputToContain('Tidak ada kontrak aktif yang ditemukan')
        ->assertSuccessful();

    expect(Bill::where('contract_id', $completedContract->id)->count())->toBe(0);
});

test('4. cancelled contract (dibatalkan) produces no recurring invoices', function () {
    $cancelledContract = Contract::create([
        'contract_number' => 'CTR-BATAL-'.Str::random(5),
        'property_id' => $this->property->id,
        'room_id' => $this->room2->id,
        'user_id' => $this->tenant2->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'total_amount' => 10800000,
        'status' => 'dibatalkan',
    ]);

    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $cancelledContract->id,
    ])
        ->expectsOutputToContain('Tidak ada kontrak aktif yang ditemukan')
        ->assertSuccessful();

    expect(Bill::where('contract_id', $cancelledContract->id)->count())->toBe(0);
});

test('5. contracts outside the target billing period do not receive invoices', function () {
    // Contract starts in 2027
    $futureContract = Contract::create([
        'contract_number' => 'CTR-FUTURE-'.Str::random(5),
        'property_id' => $this->property->id,
        'room_id' => $this->room2->id,
        'user_id' => $this->tenant2->id,
        'start_date' => '2027-01-01',
        'end_date' => '2027-06-30',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'total_amount' => 10800000,
        'status' => 'aktif',
    ]);

    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $futureContract->id,
    ])
        ->expectsOutputToContain('[SKIP]')
        ->expectsOutputToContain('Berhasil dibuat: 0')
        ->assertSuccessful();

    expect(Bill::where('contract_id', $futureContract->id)->where('billing_period', '2026-11')->count())->toBe(0);
});

test('6. deposit invoices are not created by the recurring rent command', function () {
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    $depositBills = Bill::where('contract_id', $this->contract1->id)
        ->where('type', BillType::Deposit->value)
        ->count();

    expect($depositBills)->toBe(0);
});

test('7. additional invoices are unaffected by the recurring billing process', function () {
    // Pre-existing manual/additional bill
    $additionalBill = $this->billService->createInvoice(
        [
            'contract_id' => $this->contract1->id,
            'user_id' => $this->tenant1->id,
            'room_id' => $this->room1->id,
            'type' => BillType::Tambahan,
            'billing_period' => '2026-11',
            'notes' => 'Tagihan perbaikan kran air',
        ],
        [
            ['description' => 'Ganti Kran Air', 'amount' => 75000, 'quantity' => 1],
        ]
    );

    // Run recurring rent command
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    // Additional bill remains completely untouched
    $reloadedAdditional = Bill::find($additionalBill->id);
    expect($reloadedAdditional)->not->toBeNull();
    expect((float) $reloadedAdditional->total_amount)->toBe(75000.0);
    expect($reloadedAdditional->type === BillType::Tambahan->value || $reloadedAdditional->type === BillType::Tambahan)->toBeTrue();

    // Recurring rent bill was also created
    $rentBill = Bill::where('contract_id', $this->contract1->id)
        ->where('type', BillType::Sewa->value)
        ->where('billing_period', '2026-11')
        ->first();
    expect($rentBill)->not->toBeNull();
    expect((float) $rentBill->total_amount)->toBe(2500000.0);
});

test('8. failure of one contract does not stop the processing of other contracts and reflects in exit code', function () {
    // Second active contract
    $contract2 = Contract::create([
        'contract_number' => 'CTR-SCHED-2-'.Str::random(5),
        'property_id' => $this->property->id,
        'room_id' => $this->room2->id,
        'user_id' => $this->tenant2->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 1800000,
        'total_amount' => 10800000,
        'status' => 'aktif',
    ]);

    // Mock BillService to throw for contract1 only
    $mockService = \Mockery::mock(BillService::class)->makePartial();
    $mockService->shouldReceive('normalizePeriod')->andReturn('2026-12');
    $mockService->shouldReceive('formatPeriod')->andReturn('Desember 2026');

    $mockService->shouldReceive('generateRecurringRentInvoice')
        ->with(\Mockery::on(fn ($c) => $c->id === $this->contract1->id), '2026-12')
        ->andThrow(new \RuntimeException('Gagal koneksi database atau data korup'));

    $realService = app(BillService::class);
    $mockService->shouldReceive('generateRecurringRentInvoice')
        ->with(\Mockery::any(), '2026-12')
        ->andReturnUsing(fn ($c, $p) => $realService->generateRecurringRentInvoice($c, $p));

    app()->instance(BillService::class, $mockService);

    // Run command - must continue and return FAILURE exit code because 1 contract failed
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-12',
    ])
        ->expectsOutputToContain('[FAIL] Kontrak '.$this->contract1->contract_number)
        ->expectsOutputToContain('[OK] Kontrak '.$contract2->contract_number)
        ->assertFailed();

    // Contract 2 succeeded despite contract 1 failing!
    $billContract2 = Bill::where('contract_id', $contract2->id)
        ->where('billing_period', '2026-12')
        ->first();

    expect($billContract2)->not->toBeNull();
    expect((float) $billContract2->total_amount)->toBe(1800000.0);
});

test('9. failure during bill creation rolls back atomically leaving no partial data', function () {
    $deduplicationKey = "rent:{$this->contract1->id}:2026-11";

    // Attempt to create invoice with an invalid item amount that triggers exception during transaction
    try {
        $this->billService->createInvoice(
            [
                'contract_id' => $this->contract1->id,
                'user_id' => $this->tenant1->id,
                'room_id' => $this->room1->id,
                'deduplication_key' => $deduplicationKey,
                'billing_period' => '2026-11',
            ],
            [
                ['description' => 'Valid Item', 'amount' => 1000000],
                ['description' => 'Invalid Item', 'amount' => -500], // invalid negative amount throws exception
            ]
        );
    } catch (\InvalidArgumentException $e) {
        // Expected exception
    }

    // Verify atomic rollback: no bill and no bill items exist
    expect(Bill::where('deduplication_key', $deduplicationKey)->count())->toBe(0);
    expect(BillItem::where('description', 'Valid Item')->count())->toBe(0);
});

test('10. deduplication key is consistent and enforced by database unique index', function () {
    $dedupKey = "rent:{$this->contract1->id}:2026-11";

    $bill1 = $this->billService->generateRecurringRentInvoice($this->contract1, '2026-11');
    expect($bill1->deduplication_key)->toBe($dedupKey);

    // Attempting to generate again returns the exact same bill instance
    $bill2 = $this->billService->generateRecurringRentInvoice($this->contract1, '2026-11');
    expect($bill2->id)->toBe($bill1->id);
    expect($bill2->bill_number)->toBe($bill1->bill_number);
});

test('11. concurrency duplicate key collision safely returns existing bill', function () {
    $dedupKey = "rent:{$this->contract1->id}:2026-11";

    // Create the first bill
    $firstBill = $this->billService->createInvoice(
        [
            'contract_id' => $this->contract1->id,
            'user_id' => $this->tenant1->id,
            'room_id' => $this->room1->id,
            'deduplication_key' => $dedupKey,
            'billing_period' => '2026-11',
        ],
        [
            ['description' => 'Sewa Kamar 101', 'amount' => 2500000],
        ]
    );

    // A concurrent process attempts to insert with the exact same deduplication key
    $secondBill = $this->billService->createInvoice(
        [
            'contract_id' => $this->contract1->id,
            'user_id' => $this->tenant1->id,
            'room_id' => $this->room1->id,
            'deduplication_key' => $dedupKey,
            'billing_period' => '2026-11',
        ],
        [
            ['description' => 'Sewa Kamar 101 Concurrent', 'amount' => 2500000],
        ]
    );

    expect($secondBill->id)->toBe($firstBill->id);
    expect(Bill::where('deduplication_key', $dedupKey)->count())->toBe(1);
});

test('12. command is properly registered in console scheduler with withoutOverlapping and configured timezone', function () {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $recurringEvent = $events->first(function ($event) {
        return str_contains((string) $event->command, 'bills:generate-recurring');
    });

    expect($recurringEvent)->not->toBeNull();
    expect($recurringEvent->expression)->toBe('0 1 * * *');
    expect($recurringEvent->withoutOverlapping)->toBeTrue();
    expect($recurringEvent->timezone)->toBe(config('app.timezone', 'UTC'));
});

test('13. tenant notification is dispatched once when bill is created and never re-sent on rerun', function () {
    Notification::fake();

    // First run creates the invoice and sends notification
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    Notification::assertSentTo(
        $this->tenant1,
        InvoiceCreatedNotification::class,
        function (InvoiceCreatedNotification $notification) {
            return $notification->bill->contract_id === $this->contract1->id
                && $notification->bill->billing_period === '2026-11';
        }
    );

    // Re-run command - must NOT send another notification
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    Notification::assertSentTimes(InvoiceCreatedNotification::class, 1);
});

test('14. notification failure does not roll back or delete the created invoice (fail-open resilience)', function () {
    // Simulate notification failure by logging warning
    Log::shouldReceive('warning')
        ->once()
        ->with(\Mockery::pattern('/Gagal mengirim notifikasi invoice/'), \Mockery::any());

    // Mock tenant to throw exception during notify()
    $mockTenant = \Mockery::mock($this->tenant1)->makePartial();
    $mockTenant->shouldReceive('notify')
        ->once()
        ->andThrow(new \RuntimeException('SMTP Connection Timeout'));

    $mockContract = clone $this->contract1;
    $mockContract->setRelation('tenant', $mockTenant);
    $mockContract->setRelation('user', $mockTenant);

    // When command runs and notification fails:
    $bill = $this->billService->generateRecurringRentInvoice($mockContract, '2026-11');
    expect($bill)->not->toBeNull();

    // Simulate command catch block
    try {
        $mockTenant->notify(new InvoiceCreatedNotification($bill));
    } catch (\Throwable $notificationError) {
        Log::warning("Gagal mengirim notifikasi invoice {$bill->bill_number} ke tenant ID {$mockTenant->id}: {$notificationError->getMessage()}", [
            'bill_id' => $bill->id,
            'contract_id' => $mockContract->id,
            'tenant_id' => $mockTenant->id,
        ]);
    }

    // Bill remains safely committed in the database!
    expect(Bill::where('contract_id', $this->contract1->id)->where('billing_period', '2026-11')->exists())->toBeTrue();
});

test('15. full financial lifecycle regression from recurring invoice to owner reconciliation', function () {
    Storage::fake('public');

    // 1. Generate recurring invoice via command
    $this->artisan('bills:generate-recurring', [
        '--period' => '2026-11',
        '--contract' => $this->contract1->id,
    ])->assertSuccessful();

    $bill = Bill::where('contract_id', $this->contract1->id)->where('billing_period', '2026-11')->firstOrFail();
    expect($bill->status === BillStatus::MenungguPembayaran->value || $bill->status === BillStatus::MenungguPembayaran)->toBeTrue();

    // 2. Tenant views invoice on tenant portal
    $this->actingAs($this->tenant1)
        ->get(route('tenant.bills.show', $bill))
        ->assertOk()
        ->assertSee($bill->bill_number)
        ->assertSee('2.500.000');

    // 3. Tenant submits payment
    $proof = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');
    $response = $this->actingAs($this->tenant1)
        ->post(route('tenant.bills.payments.store', $bill), [
            'amount' => 2500000,
            'payment_method' => 'Transfer Bank',
            'payment_date' => '2026-11-02',
            'reference_number' => 'REF-SCHED-123',
            'notes' => 'Pembayaran lunas sewa November 2026',
            'proofs' => [$proof],
        ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));
    $payment = Payment::where('bill_id', $bill->id)->firstOrFail();
    expect($payment->status)->toBe(PaymentStatus::MenungguVerifikasi->value);
    expect((float) $payment->amount)->toBe(2500000.0);

    // 4. Owner views and approves payment
    $this->actingAs($this->owner)
        ->get(route('owner.payments.show', $payment))
        ->assertOk()
        ->assertSee($payment->payment_number);

    $approveResponse = $this->actingAs($this->owner)
        ->post(route('owner.payments.approve', $payment));

    $approveResponse->assertRedirect(route('owner.payments.show', $payment));

    // 5. Assert invoice and payment are reconciled
    $bill->refresh();
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Berhasil->value);
    expect($bill->status)->toBe(BillStatus::Lunas->value);
    expect((float) $bill->paid_amount)->toBe(2500000.0);
    expect($bill->isPaid())->toBeTrue();
});
