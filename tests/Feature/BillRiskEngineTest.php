<?php

declare(strict_types=1);

use App\Enums\BillStatus;
use App\Enums\DelinquencyRiskTier;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillRiskScore;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\BillRiskService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.ai.base_url', 'http://127.0.0.1:8001');
    Config::set('services.ai.secret', 'test_secret_for_pest_testing_123');
    Config::set('services.ai.timeout', 3);

    // Create 2 test owners
    $this->ownerA = User::firstOrCreate(
        ['email' => 'owner_risk_a@huniapp.test'],
        ['name' => 'Owner Risk A', 'role' => UserRole::Owner, 'password' => Hash::make('password')]
    );

    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_risk_b@huniapp.test'],
        ['name' => 'Owner Risk B', 'role' => UserRole::Owner, 'password' => Hash::make('password')]
    );

    // Create tenant
    $this->tenant = User::firstOrCreate(
        ['email' => 'tenant_risk_user@huniapp.test'],
        ['name' => 'Tenant Risk User', 'role' => UserRole::Tenant, 'password' => Hash::make('password')]
    );

    // Create property A and room A
    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'slug' => 'kos-risk-alpha',
        'name' => 'Kos Risk Alpha',
        'address' => 'Jl. Risk Alpha No. 1',
        'city' => 'Jakarta',
    ]);

    $this->roomA = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => 'RA-101',
        'price' => 1500000.00,
        'status' => 'terisi',
    ]);

    // Create property B
    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'slug' => 'kos-risk-beta',
        'name' => 'Kos Risk Beta',
        'address' => 'Jl. Risk Beta No. 2',
        'city' => 'Bandung',
    ]);

    $this->contractA = Contract::create([
        'contract_number' => 'CTR-RISK-001',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenant->id,
        'start_date' => '2026-08-01',
        'end_date' => '2027-08-01',
        'monthly_rent' => 1500000.00,
        'total_amount' => 18000000.00,
        'deposit_amount' => 500000.00,
        'status' => 'aktif',
    ]);
});

test('extractFeatures prevents future payment data leakage by respecting asOf timestamp', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-LEAK-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 1500000.00,
        'status' => BillStatus::Lunas->value,
    ]);

    // Payment made on Oct 12 and verified on Oct 12
    $payment = new Payment([
        'payment_number' => 'PAY-LEAK-001',
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 1500000.00,
        'payment_method' => 'transfer',
        'payment_date' => '2026-10-12 10:00:00',
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerA->id,
        'verified_at' => '2026-10-12 11:00:00',
    ]);
    $payment->timestamps = false;
    $payment->created_at = Carbon::parse('2026-10-12 10:00:00');
    $payment->updated_at = Carbon::parse('2026-10-12 11:00:00');
    $payment->save();

    $service = new BillRiskService;

    // Evaluated at Oct 5 (before payment): paid amount must be 0 and status must be menunggu_pembayaran
    $featuresAsOfOct5 = $service->extractFeatures($bill, Carbon::parse('2026-10-05 12:00:00'));
    expect($featuresAsOfOct5['paid_amount'])->toBe(0.0)
        ->and($featuresAsOfOct5['status'])->toBe('menunggu_pembayaran');

    // Evaluated at Oct 11 (after due date, but before payment on Oct 12): paid amount must be 0 and status must be jatuh_tempo
    $featuresAsOfOct11 = $service->extractFeatures($bill, Carbon::parse('2026-10-11 12:00:00'));
    expect($featuresAsOfOct11['paid_amount'])->toBe(0.0)
        ->and($featuresAsOfOct11['status'])->toBe('jatuh_tempo');

    // Evaluated at Oct 15 (after verified payment): paid amount must be 1.500.000 and status must be lunas
    $featuresAsOfOct15 = $service->extractFeatures($bill, Carbon::parse('2026-10-15 12:00:00'));
    expect($featuresAsOfOct15['paid_amount'])->toBe(1500000.0)
        ->and($featuresAsOfOct15['status'])->toBe('lunas');
});

test('extractFeatures correctly computes past bill delinquency history without future leakage', function () {
    // Past Bill 1: Issued Sept 1, due Sept 10. Paid on Sept 5 (on time).
    $pastBill1 = Bill::create([
        'bill_number' => 'INV-HIST-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-09',
        'issue_date' => '2026-09-01',
        'due_date' => '2026-09-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 1500000.00,
        'status' => BillStatus::Lunas->value,
    ]);
    Payment::create([
        'payment_number' => 'PAY-HIST-001',
        'bill_id' => $pastBill1->id,
        'user_id' => $this->tenant->id,
        'amount' => 1500000.00,
        'payment_method' => 'transfer',
        'payment_date' => '2026-09-05 10:00:00',
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => '2026-09-05 10:00:00',
    ]);

    // Past Bill 2: Issued August 1, due August 10. Paid on August 20 (late!).
    $pastBill2 = Bill::create([
        'bill_number' => 'INV-HIST-002',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-08',
        'issue_date' => '2026-08-01',
        'due_date' => '2026-08-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 1500000.00,
        'status' => BillStatus::Lunas->value,
    ]);
    Payment::create([
        'payment_number' => 'PAY-HIST-002',
        'bill_id' => $pastBill2->id,
        'user_id' => $this->tenant->id,
        'amount' => 1500000.00,
        'payment_method' => 'transfer',
        'payment_date' => '2026-08-20 10:00:00',
        'status' => PaymentStatus::Berhasil->value,
        'verified_at' => '2026-08-20 10:00:00',
    ]);

    // Current Bill: Issued Oct 1
    $currentBill = Bill::create([
        'bill_number' => 'INV-CURR-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $service = new BillRiskService;
    $features = $service->extractFeatures($currentBill, Carbon::parse('2026-10-02 10:00:00'));

    expect($features['past_bills_count'])->toBe(2)
        ->and($features['past_late_bills_count'])->toBe(1); // PastBill2 was paid after August 10!
});

test('evaluateBill persists risk score and updates idempotently on re-evaluation', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-EVAL-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    Http::fake([
        'http://127.0.0.1:8001/api/v1/risk/evaluate' => Http::sequence()
            ->push([
                'bill_id' => $bill->id,
                'property_id' => $this->propertyA->id,
                'risk_score' => 25.0,
                'risk_tier' => 'rendah',
                'evaluator_type' => 'rule_based_heuristic',
                'model_version' => '1.0.0',
                'contributing_factors' => [
                    ['code' => 'baseline_uncertainty', 'factor' => 'Baseline', 'impact' => 10.0, 'description' => 'Baseline'],
                    ['code' => 'due_date_proximity', 'factor' => 'Jatuh Tempo', 'impact' => 5.0, 'description' => 'Mendekati'],
                    ['code' => 'unpaid_balance_ratio', 'factor' => 'Unpaid', 'impact' => 10.0, 'description' => '100%'],
                ],
                'metadata' => ['is_eligible' => true],
                'evaluated_at' => '2026-10-05T10:00:00Z',
            ], 200)
            ->push([
                'bill_id' => $bill->id,
                'property_id' => $this->propertyA->id,
                'risk_score' => 65.0,
                'risk_tier' => 'sedang',
                'evaluator_type' => 'rule_based_heuristic',
                'model_version' => '1.0.0',
                'contributing_factors' => [
                    ['code' => 'overdue_severity', 'factor' => 'Overdue', 'impact' => 40.0, 'description' => 'Overdue 5 days'],
                ],
                'metadata' => ['is_eligible' => true],
                'evaluated_at' => '2026-10-15T10:00:00Z',
            ], 200),
    ]);

    $service = new BillRiskService;

    // First evaluation: creates record
    $score1 = $service->evaluateBill($bill, Carbon::parse('2026-10-05 10:00:00'));

    expect($score1)->not->toBeNull()
        ->and((float) $score1->risk_score)->toBe(25.0)
        ->and($score1->risk_tier)->toBe(DelinquencyRiskTier::Rendah)
        ->and(BillRiskScore::where('bill_id', $bill->id)->count())->toBe(1);

    // Re-evaluation with updated score: updates record in place
    $score2 = $service->evaluateBill($bill, Carbon::parse('2026-10-15 10:00:00'));

    expect($score2)->not->toBeNull()
        ->and((float) $score2->risk_score)->toBe(65.0)
        ->and($score2->risk_tier)->toBe(DelinquencyRiskTier::Sedang)
        ->and(BillRiskScore::where('bill_id', $bill->id)->count())->toBe(1);
});

test('evaluateBill handles AI service timeout or network failure safely without breaking billing workflow', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-FAIL-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    Http::fake([
        'http://127.0.0.1:8001/api/v1/risk/evaluate' => function () {
            throw new ConnectionException('Connection timed out');
        },
    ]);

    $service = new BillRiskService;
    $result = $service->evaluateBill($bill);

    // Must return null gracefully and not throw unhandled exception
    expect($result)->toBeNull()
        ->and(BillRiskScore::where('bill_id', $bill->id)->exists())->toBeFalse();
});

test('property isolation and authorization policy protects risk scores', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-AUTH-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $score = BillRiskScore::create([
        'bill_id' => $bill->id,
        'risk_score' => 30.0,
        'risk_tier' => DelinquencyRiskTier::Rendah,
        'evaluator_type' => 'rule_based_heuristic',
        'model_version' => '1.0.0',
        'contributing_factors' => [],
        'evaluated_at' => now(),
    ]);

    $service = new BillRiskService;

    // Owner A (property owner) is authorized
    expect($service->isAuthorizedOwner($this->ownerA, $bill))->toBeTrue();
    expect(Gate::forUser($this->ownerA)->allows('view', $score))->toBeTrue();

    // Owner B (different property owner) is NOT authorized
    expect($service->isAuthorizedOwner($this->ownerB, $bill))->toBeFalse();
    expect(Gate::forUser($this->ownerB)->allows('view', $score))->toBeFalse();

    // Tenant is NOT authorized to view risk score
    expect($service->isAuthorizedOwner($this->tenant, $bill))->toBeFalse();
    expect(Gate::forUser($this->tenant)->allows('view', $score))->toBeFalse();
});

test('evaluateBill handles unconfigured secret or HTTP 500 server errors gracefully', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-ERR-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // Scenario 1: HTTP 500 from AI service
    Http::fake([
        'http://127.0.0.1:8001/api/v1/risk/evaluate' => Http::response(['detail' => 'Internal server error'], 500),
    ]);

    $service = new BillRiskService;
    $res500 = $service->evaluateBill($bill);
    expect($res500)->toBeNull();

    // Scenario 2: Unconfigured secret
    Config::set('services.ai.secret', '');
    $resNoSecret = $service->evaluateBill($bill);
    expect($resNoSecret)->toBeNull();
});

test('cancelled and paid bills are evaluated as zero risk with clear explanation', function () {
    $cancelledBill = Bill::create([
        'bill_number' => 'INV-CANC-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 0.00,
        'status' => BillStatus::Dibatalkan->value,
    ]);

    $paidBill = Bill::create([
        'bill_number' => 'INV-PAID-001',
        'contract_id' => $this->contractA->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->roomA->id,
        'billing_period' => '2026-10',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'total_amount' => 1500000.00,
        'paid_amount' => 1500000.00,
        'status' => BillStatus::Lunas->value,
    ]);

    Http::fake([
        'http://127.0.0.1:8001/api/v1/risk/evaluate' => Http::sequence()
            ->push([
                'bill_id' => $cancelledBill->id,
                'property_id' => $this->propertyA->id,
                'risk_score' => 0.0,
                'risk_tier' => 'rendah',
                'evaluator_type' => 'rule_based_heuristic',
                'model_version' => '1.0.0',
                'contributing_factors' => [
                    ['code' => 'bill_cancelled', 'factor' => 'Dibatalkan', 'impact' => 0.0, 'description' => 'Tagihan dibatalkan'],
                ],
                'metadata' => ['is_eligible' => false],
                'evaluated_at' => '2026-10-15T10:00:00Z',
            ], 200)
            ->push([
                'bill_id' => $paidBill->id,
                'property_id' => $this->propertyA->id,
                'risk_score' => 0.0,
                'risk_tier' => 'rendah',
                'evaluator_type' => 'rule_based_heuristic',
                'model_version' => '1.0.0',
                'contributing_factors' => [
                    ['code' => 'bill_fully_paid', 'factor' => 'Lunas', 'impact' => 0.0, 'description' => 'Tagihan lunas'],
                ],
                'metadata' => ['is_eligible' => false],
                'evaluated_at' => '2026-10-15T10:00:00Z',
            ], 200),
    ]);

    $service = new BillRiskService;

    $scoreCancelled = $service->evaluateBill($cancelledBill);
    expect($scoreCancelled)->not->toBeNull()
        ->and((float) $scoreCancelled->risk_score)->toBe(0.0)
        ->and($scoreCancelled->risk_tier)->toBe(DelinquencyRiskTier::Rendah);

    $scorePaid = $service->evaluateBill($paidBill);
    expect($scorePaid)->not->toBeNull()
        ->and((float) $scorePaid->risk_score)->toBe(0.0)
        ->and($scorePaid->risk_tier)->toBe(DelinquencyRiskTier::Rendah);
});
