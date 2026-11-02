<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->analyticsService = app(AnalyticsService::class);

    // Setup Owner A
    $this->ownerA = User::create([
        'name' => 'Owner Analytics A',
        'email' => 'owner_analytics_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Sejahtera A '.Str::random(5),
        'slug' => 'kos-sejahtera-a-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Merdeka No. 10',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 4,
        'status' => 'aktif',
    ]);

    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => 'A1',
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'terisi',
    ]);

    $this->roomA2 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => 'A2',
        'floor' => 1,
        'type' => 'standar',
        'price' => 1500000,
        'status' => 'kosong',
    ]);

    // Setup Owner B
    $this->ownerB = User::create([
        'name' => 'Owner Analytics B',
        'email' => 'owner_analytics_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Harmoni B '.Str::random(5),
        'slug' => 'kos-harmoni-b-'.Str::random(8),
        'type' => 'putra',
        'address' => 'Jl. Sudirman No. 5',
        'city' => 'Bandung',
        'district' => 'Coblong',
        'postal_code' => '40132',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => 'B1',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'terisi',
    ]);

    // Tenant
    $this->tenant = User::create([
        'name' => 'Tenant Analytics',
        'email' => 'tenant_analytics_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

test('owner can access analytics dashboard', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.analytics.index'));

    $response->assertStatus(200);
    $response->assertSee('Laporan & Analitik');
    $response->assertSee('Total Pendapatan');
    $response->assertSee('Tren Pendapatan');
    $response->assertSee('Status Pembayaran');
    $response->assertSee('Tingkat Hunian');
    $response->assertSee('Komposisi Pendapatan');
});

test('guest is redirected to login when accessing analytics', function () {
    $response = $this->get(route('owner.analytics.index'));

    $response->assertRedirect(route('login'));
});

test('tenant cannot access owner analytics dashboard', function () {
    $response = $this->actingAs($this->tenant)->get(route('owner.analytics.index'));

    $response->assertStatus(403);
});

test('owner cannot access another owners property via filter parameter and gets 403', function () {
    // Owner A attempts to filter using property B ID
    $response = $this->actingAs($this->ownerA)->get(route('owner.analytics.index', [
        'property_id' => $this->propertyB->id,
    ]));

    $response->assertStatus(403);
});

test('owner can filter by their own property successfully', function () {
    $response = $this->actingAs($this->ownerA)->get(route('owner.analytics.index', [
        'property_id' => $this->propertyA->id,
    ]));

    $response->assertStatus(200);
    $response->assertSee($this->propertyA->name);
});

test('total revenue only includes verified successful payments and ignores pending or rejected payments', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'deposit_amount' => 500000,
        'status' => ContractStatus::Aktif->value,
    ]);

    $bill = Bill::create([
        'bill_number' => 'INV-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 1500000,
        'paid_amount' => 1000000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // 1. Successful verified payment -> MUST be counted
    Payment::create([
        'payment_number' => 'PAY-SUCC-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerA->id,
        'verified_at' => now(),
    ]);

    // 2. Pending payment -> MUST NOT be counted
    Payment::create([
        'payment_number' => 'PAY-PEND-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 300000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    // 3. Rejected payment -> MUST NOT be counted
    Payment::create([
        'payment_number' => 'PAY-REJ-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 200000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::Ditolak->value,
        'rejection_reason' => 'Bukti buram',
    ]);

    $data = $this->analyticsService->getAnalyticsData($this->ownerA, [
        'period_type' => 'month',
        'period' => now()->format('Y-m'),
    ]);

    expect($data['kpis']['totalRevenue'])->toEqual(1000000.0);
});

test('payment for previous month invoice verified in current month is recognized in current month revenue', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-PREV-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonths(5)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'status' => ContractStatus::Aktif->value,
    ]);

    // Bill issued last month
    $prevMonth = now()->subMonth();
    $bill = Bill::create([
        'bill_number' => 'INV-PREV-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => $prevMonth->format('Y-m'),
        'issue_date' => $prevMonth->startOfMonth()->toDateString(),
        'due_date' => $prevMonth->copy()->addDays(7)->toDateString(),
        'total_amount' => 1500000,
        'paid_amount' => 1500000,
        'status' => BillStatus::Lunas->value,
    ]);

    // Paid & verified THIS month
    Payment::create([
        'payment_number' => 'PAY-NOW-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1500000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerA->id,
        'verified_at' => now(),
    ]);

    // 1. Check current month -> Should include 1,500,000
    $currentData = $this->analyticsService->getAnalyticsData($this->ownerA, [
        'period_type' => 'month',
        'period' => now()->format('Y-m'),
    ]);
    expect($currentData['kpis']['totalRevenue'])->toEqual(1500000.0);

    // 2. Check previous month -> Should be 0 since payment was not recognized last month
    $prevData = $this->analyticsService->getAnalyticsData($this->ownerA, [
        'period_type' => 'month',
        'period' => $prevMonth->format('Y-m'),
    ]);
    expect($prevData['kpis']['totalRevenue'])->toEqual(0.0);
});

test('total overdue arrears calculates remaining balance and excludes paid or cancelled bills', function () {
    $contract = Contract::create([
        'contract_number' => 'CTR-ARREARS-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->subMonths(3)->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => ContractStatus::Aktif->value,
    ]);

    // 1. Overdue bill with partial payment (Rp 2,000,000 total - Rp 500,000 paid = Rp 1,500,000 overdue)
    Bill::create([
        'bill_number' => 'INV-LATE-1-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => now()->subMonth()->format('Y-m'),
        'issue_date' => now()->subMonth()->toDateString(),
        'due_date' => now()->subDays(10)->toDateString(), // Overdue
        'total_amount' => 2000000,
        'paid_amount' => 500000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // 2. Fully paid overdue bill -> MUST NOT be in arrears
    Bill::create([
        'bill_number' => 'INV-PAID-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => now()->subMonths(2)->format('Y-m'),
        'issue_date' => now()->subMonths(2)->toDateString(),
        'due_date' => now()->subDays(40)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => BillStatus::Lunas->value,
    ]);

    // 3. Cancelled bill past due -> MUST NOT be in arrears
    Bill::create([
        'bill_number' => 'INV-CANC-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => now()->subMonth()->format('Y-m'),
        'issue_date' => now()->subMonth()->toDateString(),
        'due_date' => now()->subDays(5)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::Dibatalkan->value,
    ]);

    // 4. Future bill not yet overdue -> MUST NOT be in arrears
    Bill::create([
        'bill_number' => 'INV-FUTR-'.Str::random(6),
        'contract_id' => $contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(), // Future
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $data = $this->analyticsService->getAnalyticsData($this->ownerA);

    expect($data['kpis']['totalOverdueAmount'])->toEqual(1500000.0)
        ->and($data['kpis']['overdueTenantsCount'])->toEqual(1);
});

test('room occupancy rate calculates correctly and handles zero rooms condition', function () {
    // Owner A has 2 rooms: roomA1 (terisi), roomA2 (kosong) -> 50%
    $dataA = $this->analyticsService->getAnalyticsData($this->ownerA);
    expect($dataA['kpis']['totalRooms'])->toEqual(2)
        ->and($dataA['kpis']['occupiedRooms'])->toEqual(1)
        ->and($dataA['kpis']['occupancyRate'])->toEqual(50);

    // Empty Owner with 0 rooms
    $emptyOwner = User::create([
        'name' => 'Empty Owner',
        'email' => 'empty_owner_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $dataEmpty = $this->analyticsService->getAnalyticsData($emptyOwner);
    expect($dataEmpty['kpis']['totalRooms'])->toEqual(0)
        ->and($dataEmpty['kpis']['occupancyRate'])->toEqual(0);
});

test('period filtering for monthly quarterly and yearly works properly', function () {
    // Test Monthly
    $resMonth = $this->actingAs($this->ownerA)->get(route('owner.analytics.index', [
        'period_type' => 'month',
        'period' => '2026-08',
    ]));
    $resMonth->assertStatus(200);
    $resMonth->assertSee('Agustus 2026');

    // Test Quarterly
    $resQuarter = $this->actingAs($this->ownerA)->get(route('owner.analytics.index', [
        'period_type' => 'quarter',
        'period' => '2026-Q3',
    ]));
    $resQuarter->assertStatus(200);
    $resQuarter->assertSee('Triwulan 3 2026');

    // Test Yearly
    $resYear = $this->actingAs($this->ownerA)->get(route('owner.analytics.index', [
        'period_type' => 'year',
        'period' => '2026',
    ]));
    $resYear->assertStatus(200);
    $resYear->assertSee('Tahun 2026');
});

test('complaint categories widget groups complaints by actual enum categories', function () {
    Complaint::create([
        'ticket_number' => 'KMP-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'title' => 'Lampu Mati',
        'description' => 'Lampu kamar mandi mati',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'title' => 'Keran Bocor',
        'description' => 'Air menetes terus',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $data = $this->analyticsService->getAnalyticsData($this->ownerA);
    $categories = $data['widgets']['topComplaintCategories'];

    expect($categories)->toHaveCount(2)
        ->and($categories->pluck('category')->toArray())->toContain('Listrik', 'Air & Sanitasi');
});

test('multi-owner isolation: owner A data never leaks into owner B analytics', function () {
    // Owner B contract and verified payment of Rp 3,000,000
    $contractB = Contract::create([
        'contract_number' => 'CTR-B-'.Str::random(6),
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => ContractStatus::Aktif->value,
    ]);

    $billB = Bill::create([
        'bill_number' => 'INV-B-'.Str::random(6),
        'contract_id' => $contractB->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomB1->id,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 3000000,
        'paid_amount' => 3000000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-B-'.Str::random(6),
        'bill_id' => $billB->id,
        'user_id' => $this->tenant->id,
        'amount' => 3000000,
        'payment_method' => 'Transfer Bank',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerB->id,
        'verified_at' => now(),
    ]);

    // Check Owner A revenue -> MUST be 0 (none of Owner B's 3,000,000 should appear)
    $dataA = $this->analyticsService->getAnalyticsData($this->ownerA);
    expect($dataA['kpis']['totalRevenue'])->toEqual(0.0);

    // Check Owner B revenue -> Exactly 3,000,000
    $dataB = $this->analyticsService->getAnalyticsData($this->ownerB);
    expect($dataB['kpis']['totalRevenue'])->toEqual(3000000.0);
});
