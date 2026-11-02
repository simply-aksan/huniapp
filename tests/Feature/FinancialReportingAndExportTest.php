<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->reportService = app(ReportService::class);

    // Setup Owner A
    $this->ownerA = User::create([
        'name' => 'Owner Report A',
        'email' => 'owner_report_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Maju A '.Str::random(5),
        'slug' => 'kos-maju-a-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Gatot Subroto No. 1',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 3,
        'status' => 'aktif',
    ]);

    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '101',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'terisi',
    ]);

    $this->roomA2 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '102',
        'floor' => 1,
        'type' => 'deluxe',
        'price' => 3000000,
        'status' => 'kosong',
    ]);

    // Setup Owner B (for cross-owner isolation testing)
    $this->ownerB = User::create([
        'name' => 'Owner Report B',
        'email' => 'owner_report_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Berkah B '.Str::random(5),
        'slug' => 'kos-berkah-b-'.Str::random(8),
        'type' => 'putri',
        'address' => 'Jl. Dago No. 20',
        'city' => 'Bandung',
        'district' => 'Coblong',
        'postal_code' => '40132',
        'total_rooms' => 2,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => '201',
        'floor' => 2,
        'type' => 'vip',
        'price' => 4000000,
        'status' => 'terisi',
    ]);

    // Setup Tenant
    $this->tenant = User::create([
        'name' => 'Penyewa Teladan',
        'email' => 'tenant_report_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractA = Contract::create([
        'contract_number' => 'CTR-A-'.Str::random(6),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->endOfMonth()->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);
});

test('1. owner can access report print and export', function () {
    $this->actingAs($this->ownerA);

    $printResponse = $this->get(route('owner.reports.print', [
        'report_type' => 'revenue',
    ]));
    $printResponse->assertStatus(200);

    $exportResponse = $this->get(route('owner.reports.export', [
        'report_type' => 'revenue',
    ]));
    $exportResponse->assertStatus(200);
});

test('2. guest redirected to login', function () {
    $printResponse = $this->get(route('owner.reports.print'));
    $printResponse->assertRedirect(route('login'));

    $exportResponse = $this->get(route('owner.reports.export'));
    $exportResponse->assertRedirect(route('login'));
});

test('3. tenant is forbidden from reports', function () {
    $this->actingAs($this->tenant);

    $printResponse = $this->get(route('owner.reports.print'));
    $printResponse->assertStatus(403);

    $exportResponse = $this->get(route('owner.reports.export'));
    $exportResponse->assertStatus(403);
});

test('4. owner cannot access other owner property in reports and export', function () {
    $this->actingAs($this->ownerA);

    // Owner A requests report with Owner B's property_id -> Aborts with 403 Forbidden
    $printResponse = $this->get(route('owner.reports.print', [
        'report_type' => 'revenue',
        'property_id' => $this->propertyB->id,
    ]));
    $printResponse->assertStatus(403);

    $exportResponse = $this->get(route('owner.reports.export', [
        'report_type' => 'revenue',
        'property_id' => $this->propertyB->id,
    ]));
    $exportResponse->assertStatus(403);
});

test('5. csv has correct content-type and content-disposition', function () {
    $this->actingAs($this->ownerA);

    $response = $this->get(route('owner.reports.export', [
        'report_type' => 'revenue',
    ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('huni-laporan-revenue-')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment');
});

test('6. headers and content of csv are consistent with report types', function () {
    $this->actingAs($this->ownerA);

    $types = ['revenue', 'payments', 'bills', 'occupancy', 'complaints', 'inventory'];

    foreach ($types as $type) {
        $response = $this->get(route('owner.reports.export', ['report_type' => $type]));
        $response->assertStatus(200);
        $content = $response->streamedContent();

        // Check UTF-8 BOM
        expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
        expect($content)->toContain('HUNI — LAPORAN');
    }
});

test('7. revenue report only counts verified successful payments', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-REV-'.Str::random(6),
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->startOfMonth(),
        'due_date' => now()->startOfMonth()->addDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-REV-OK',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_method' => 'Transfer BCA',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
        'verified_by' => $this->ownerA->id,
    ]);

    $data = $this->reportService->getReportData($this->ownerA, ['report_type' => 'revenue']);

    expect($data['summary'][0]['raw'])->toBe(2000000.0)
        ->and($data['summary'][1]['raw'])->toBe(1);
});

test('8. pending and rejected payments are not counted as revenue', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->startOfMonth(),
        'due_date' => now()->startOfMonth()->addDays(5),
        'total_amount' => 3000000,
        'paid_amount' => 1000000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // 1 Verified payment: 1,000,000
    Payment::create([
        'payment_number' => 'PAY-TEST-OK',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    // 1 Pending payment: 1,000,000
    Payment::create([
        'payment_number' => 'PAY-TEST-PENDING',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    // 1 Rejected payment: 1,000,000
    Payment::create([
        'payment_number' => 'PAY-TEST-REJECTED',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::Ditolak->value,
    ]);

    $data = $this->reportService->getReportData($this->ownerA, ['report_type' => 'revenue']);

    // Only the verified 1,000,000 counts
    expect($data['summary'][0]['raw'])->toBe(1000000.0)
        ->and($data['summary'][1]['raw'])->toBe(1);
});

test('9. partial payments are not double counted', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-PARTIAL-'.Str::random(6),
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->startOfMonth(),
        'due_date' => now()->startOfMonth()->addDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 1500000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // 1st installment: 700,000
    Payment::create([
        'payment_number' => 'PAY-PART-1',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 700000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    // 2nd installment: 800,000
    Payment::create([
        'payment_number' => 'PAY-PART-2',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 800000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    $data = $this->reportService->getReportData($this->ownerA, ['report_type' => 'revenue']);

    // Total should be exactly 700,000 + 800,000 = 1,500,000 (not full invoice total 2,000,000)
    expect($data['summary'][0]['raw'])->toBe(1500000.0)
        ->and($data['summary'][1]['raw'])->toBe(2);
});

test('10. verified_at and fallback date rule matches phase 9a', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-DATE-'.Str::random(6),
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->startOfMonth(),
        'due_date' => now()->startOfMonth()->addDays(5),
        'total_amount' => 3000000,
        'paid_amount' => 3000000,
        'status' => BillStatus::Lunas->value,
    ]);

    // Payment A: payment_date last month, but verified_at THIS month -> counted in THIS month
    Payment::create([
        'payment_number' => 'PAY-DATE-A',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer',
        'payment_date' => now()->subMonth(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    // Payment B: verified_at is null, payment_date THIS month -> fallback to payment_date, counted in THIS month
    Payment::create([
        'payment_number' => 'PAY-DATE-B',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_method' => 'Transfer',
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => null,
    ]);

    $data = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'revenue',
        'period_type' => 'month',
        'period' => now()->format('Y-m'),
    ]);

    expect($data['summary'][0]['raw'])->toBe(3000000.0)
        ->and($data['summary'][1]['raw'])->toBe(2);
});

test('11. total receivables and arrears are accurate', function () {
    // Bill 1: Overdue (due 5 days ago, outstanding 500,000)
    Bill::create([
        'bill_number' => 'INV-OVERDUE-1',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->subDays(10),
        'due_date' => now()->subDays(5),
        'total_amount' => 1500000,
        'paid_amount' => 1000000,
        'status' => BillStatus::Sebagian->value,
    ]);

    // Bill 2: Unpaid but not overdue yet (due in 5 days, outstanding 2,000,000)
    Bill::create([
        'bill_number' => 'INV-FUTURE-1',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now(),
        'due_date' => now()->addDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $data = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'bills',
        'start_date' => now()->subDays(15)->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    // Total tagihan: 1,500,000 + 2,000,000 = 3,500,000
    // Total terbayar: 1,000,000
    // Sisa piutang: 500,000 + 2,000,000 = 2,500,000
    // Tunggakan overdue: 500,000
    expect($data['summary'][0]['raw'])->toBe(3500000.0)
        ->and($data['summary'][1]['raw'])->toBe(1000000.0)
        ->and($data['summary'][2]['raw'])->toBe(2500000.0)
        ->and($data['summary'][3]['raw'])->toBe(500000.0);
});

test('12. paid and cancelled bills are not counted as arrears', function () {
    // Bill 1: Paid in full (due in past, but paid)
    Bill::create([
        'bill_number' => 'INV-PAID-PAST',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->subDays(15),
        'due_date' => now()->subDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => BillStatus::Lunas->value,
    ]);

    // Bill 2: Cancelled (due in past, but cancelled)
    Bill::create([
        'bill_number' => 'INV-CANCEL-PAST',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->subDays(15),
        'due_date' => now()->subDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::Dibatalkan->value,
    ]);

    $data = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'bills',
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->toDateString(),
    ]);

    // Overdue arrears must be 0
    expect($data['summary'][3]['raw'])->toBe(0.0);
});

test('13. date and property filters work correctly', function () {
    // Create second property for Owner A
    $propertyA2 = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Maju A2',
        'slug' => 'kos-maju-a2-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Kuningan No. 10',
        'city' => 'Jakarta Selatan',
        'district' => 'Setiabudi',
        'postal_code' => '12920',
        'total_rooms' => 1,
        'status' => 'aktif',
    ]);

    $roomA2_1 = Room::create([
        'property_id' => $propertyA2->id,
        'room_number' => '201',
        'floor' => 2,
        'type' => 'standar',
        'price' => 1800000,
        'status' => 'terisi',
    ]);

    // Bill on Property 1
    $bill1 = Bill::create([
        'bill_number' => 'INV-PROP1',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now(),
        'due_date' => now()->addDays(5),
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-PROP1',
        'bill_id' => $bill1->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    // Bill on Property 2
    $bill2 = Bill::create([
        'bill_number' => 'INV-PROP2',
        'user_id' => $this->tenant->id,
        'room_id' => $roomA2_1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now(),
        'due_date' => now()->addDays(5),
        'total_amount' => 1800000,
        'paid_amount' => 1800000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-PROP2',
        'bill_id' => $bill2->id,
        'user_id' => $this->tenant->id,
        'amount' => 1800000,
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    // Filter Property 1 only
    $dataProp1 = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'revenue',
        'property_id' => $this->propertyA->id,
    ]);
    expect($dataProp1['summary'][0]['raw'])->toBe(2000000.0)
        ->and($dataProp1['summary'][1]['raw'])->toBe(1);

    // Filter Property 2 only
    $dataProp2 = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'revenue',
        'property_id' => $propertyA2->id,
    ]);
    expect($dataProp2['summary'][0]['raw'])->toBe(1800000.0)
        ->and($dataProp2['summary'][1]['raw'])->toBe(1);
});

test('14. invalid date filter is rejected', function () {
    $this->actingAs($this->ownerA);

    // start_date after end_date
    $response = $this->get(route('owner.reports.print', [
        'report_type' => 'revenue',
        'start_date' => '2026-10-25',
        'end_date' => '2026-10-10',
    ]));

    $response->assertSessionHasErrors(['end_date']);

    // invalid report_type
    $responseInvalidType = $this->get(route('owner.reports.print', [
        'report_type' => 'invalid_query_hack_table',
    ]));

    $responseInvalidType->assertSessionHasErrors(['report_type']);
});

test('15. all reports are strictly isolated to logged-in owner', function () {
    // Create data on Owner B
    $contractB = Contract::create([
        'contract_number' => 'CTR-B-'.Str::random(6),
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->endOfMonth()->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 4000000,
        'total_amount' => 24000000,
        'status' => 'aktif',
    ]);

    $billB = Bill::create([
        'bill_number' => 'INV-OWNER-B',
        'contract_id' => $contractB->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomB1->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now(),
        'due_date' => now()->addDays(5),
        'total_amount' => 4000000,
        'paid_amount' => 4000000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-OWNER-B',
        'bill_id' => $billB->id,
        'user_id' => $this->tenant->id,
        'amount' => 4000000,
        'payment_date' => now(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => now(),
    ]);

    Complaint::create([
        'ticket_number' => 'TKT-OWNER-B',
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'user_id' => $this->tenant->id,
        'title' => 'Komplain Rahasia B',
        'description' => 'Detail masalah kos B',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    Inventory::create([
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB1->id,
        'name' => 'AC Panasonic Milik B',
        'serial_number' => 'SN-B-12345',
        'condition' => 'baik',
        'quantity' => 1,
        'purchase_price' => 5000000,
    ]);

    // Check all reports for Owner A: Owner B data must NEVER appear
    $revenueData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'revenue']);
    expect(collect($revenueData['tableRows'])->pluck('payment_number'))->not->toContain('PAY-OWNER-B');

    $paymentsData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'payments']);
    expect(collect($paymentsData['tableRows'])->pluck('payment_number'))->not->toContain('PAY-OWNER-B');

    $billsData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'bills']);
    expect(collect($billsData['tableRows'])->pluck('bill_number'))->not->toContain('INV-OWNER-B');

    $occupancyData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'occupancy']);
    expect(collect($occupancyData['tableRows'])->pluck('room_number'))->not->toContain('201');

    $complaintsData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'complaints']);
    expect(collect($complaintsData['tableRows'])->pluck('ticket_number'))->not->toContain('TKT-OWNER-B');

    $inventoryData = $this->reportService->getReportData($this->ownerA, ['report_type' => 'inventory']);
    expect(collect($inventoryData['tableRows'])->pluck('name'))->not->toContain('AC Panasonic Milik B');
});

test('16. csv formula injection is properly mitigated', function () {
    // Test formula triggers
    expect($this->reportService->sanitizeCsvCell('=1+1'))->toBe("'=1+1")
        ->and($this->reportService->sanitizeCsvCell('+SUM(A1:B1)'))->toBe("'+SUM(A1:B1)")
        ->and($this->reportService->sanitizeCsvCell('@cmd|'))->toBe("'@cmd|")
        ->and($this->reportService->sanitizeCsvCell('-DANGEROUS'))->toBe("'-DANGEROUS")
        ->and($this->reportService->sanitizeCsvCell('   =INJECTION'))->toBe("'   =INJECTION");

    // Test genuine numeric values are NOT prepended with apostrophe
    expect($this->reportService->sanitizeCsvCell(-1500000))->toBe('-1500000')
        ->and($this->reportService->sanitizeCsvCell(2500000.50))->toBe('2500000.5')
        ->and($this->reportService->sanitizeCsvCell('12345'))->toBe('12345');
});

test('17. occupancy report handles property with zero rooms gracefully without error', function () {
    $emptyProperty = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Kosong Tanpa Kamar',
        'slug' => 'kos-kosong-tanpa-kamar-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Baru No. 0',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 0,
        'status' => 'aktif',
    ]);

    $data = $this->reportService->getReportData($this->ownerA, [
        'report_type' => 'occupancy',
        'property_id' => $emptyProperty->id,
    ]);

    expect($data['summary'][0]['raw'])->toBe(0)
        ->and($data['totalRowDisplay']['occupancy_rate'])->toBe(0);

    // Also assert print view returns 200 OK without division by zero
    $this->actingAs($this->ownerA);
    $response = $this->get(route('owner.reports.print', [
        'report_type' => 'occupancy',
        'property_id' => $emptyProperty->id,
    ]));
    $response->assertStatus(200);
});

test('18. print view displays summary and proper dataset', function () {
    $this->actingAs($this->ownerA);

    $response = $this->get(route('owner.reports.print', [
        'report_type' => 'revenue',
    ]));

    $response->assertStatus(200);
    $response->assertSee('Laporan Pendapatan Kas Terverifikasi');
    $response->assertSee('Ringkasan Laporan');
    $response->assertSee('Rincian Data');
    $response->assertSee('Cetak / Simpan PDF');
    $response->assertSee('Unduh CSV');
});
