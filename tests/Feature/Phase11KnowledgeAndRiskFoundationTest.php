<?php

declare(strict_types=1);

use App\Enums\DelinquencyRiskTier;
use App\Enums\PropertyRuleCategory;
use App\Enums\UserRole;
use App\Models\AiChatLog;
use App\Models\Bill;
use App\Models\BillRiskScore;
use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\PropertyRule;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Set up test owners
    $this->ownerA = User::firstOrCreate(
        ['email' => 'owner_phase11_a@huniapp.test'],
        [
            'name' => 'Owner Phase 11 A',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_phase11_b@huniapp.test'],
        [
            'name' => 'Owner Phase 11 B',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
        ]
    );

    // Set up test tenant
    $this->tenant = User::firstOrCreate(
        ['email' => 'tenant_phase11@huniapp.test'],
        [
            'name' => 'Tenant Phase 11',
            'role' => UserRole::Tenant,
            'password' => Hash::make('password'),
        ]
    );

    // Set up properties
    $this->propertyA = Property::firstOrCreate(
        ['slug' => 'kos-phase11-a'],
        [
            'owner_id' => $this->ownerA->id,
            'name' => 'Kos Phase 11 Alpha',
            'address' => 'Jl. Kaliurang KM 5',
            'city' => 'Sleman',
            'status' => 'aktif',
        ]
    );

    $this->propertyB = Property::firstOrCreate(
        ['slug' => 'kos-phase11-b'],
        [
            'owner_id' => $this->ownerB->id,
            'name' => 'Kos Phase 11 Beta',
            'address' => 'Jl. Gejayan No. 10',
            'city' => 'Sleman',
            'status' => 'aktif',
        ]
    );

    // Set up room, contract, and bill for property A
    $this->roomA = Room::firstOrCreate(
        ['property_id' => $this->propertyA->id, 'room_number' => 'P11-A1'],
        [
            'floor' => 1,
            'type' => 'Standard',
            'price' => 1500000.00,
            'capacity' => 1,
            'status' => 'terisi',
        ]
    );

    $this->contractA = Contract::firstOrCreate(
        ['contract_number' => 'CTR-P11-001'],
        [
            'property_id' => $this->propertyA->id,
            'room_id' => $this->roomA->id,
            'user_id' => $this->tenant->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(),
            'duration_months' => 12,
            'monthly_rent' => 1500000.00,
            'total_amount' => 18000000.00,
            'deposit_amount' => 1500000.00,
            'deposit_status' => 'lunas',
            'payment_method' => 'Transfer Bank',
            'status' => 'aktif',
        ]
    );

    $this->billA = Bill::firstOrCreate(
        ['bill_number' => 'INV-P11-001'],
        [
            'contract_id' => $this->contractA->id,
            'user_id' => $this->tenant->id,
            'room_id' => $this->roomA->id,
            'type' => 'sewa',
            'billing_period' => 'Oktober 2026',
            'issue_date' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'total_amount' => 1500000.00,
            'paid_amount' => 0.00,
            'status' => 'menunggu_pembayaran',
        ]
    );
});

/*
|--------------------------------------------------------------------------
| Section 1: Schema and Table Structure Tests
|--------------------------------------------------------------------------
*/

test('phase 11 database tables exist with required columns and constraints', function () {
    // 1. property_rules table
    expect(Schema::hasTable('property_rules'))->toBeTrue();
    expect(Schema::hasColumns('property_rules', [
        'id', 'property_id', 'title', 'category', 'content', 'is_active', 'order', 'created_at', 'updated_at',
    ]))->toBeTrue();

    // 2. property_faqs table
    expect(Schema::hasTable('property_faqs'))->toBeTrue();
    expect(Schema::hasColumns('property_faqs', [
        'id', 'property_id', 'question', 'answer', 'category', 'is_active', 'order', 'created_at', 'updated_at',
    ]))->toBeTrue();

    // 3. bill_risk_scores table
    expect(Schema::hasTable('bill_risk_scores'))->toBeTrue();
    expect(Schema::hasColumns('bill_risk_scores', [
        'id', 'bill_id', 'risk_score', 'risk_tier', 'evaluator_type', 'model_version',
        'contributing_factors', 'metadata', 'evaluated_at', 'created_at', 'updated_at',
    ]))->toBeTrue();

    // 4. ai_chat_logs table
    expect(Schema::hasTable('ai_chat_logs'))->toBeTrue();
    expect(Schema::hasColumns('ai_chat_logs', [
        'id', 'user_id', 'property_id', 'session_id', 'question', 'answer', 'sources',
        'is_fallback', 'relevance_score', 'processing_time_ms', 'metadata', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Section 2: PropertyRule Model, Relationships & Scopes
|--------------------------------------------------------------------------
*/

test('property rule can be created, casts booleans and integers, and belongs to property', function () {
    $rule = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Jam Malam & Gerbang',
        'category' => PropertyRuleCategory::JamMalam->value,
        'content' => 'Pintu gerbang utama ditutup pukul 23:00 WIB demi keamanan penghuni.',
        'is_active' => true,
        'order' => 1,
    ]);

    expect($rule->exists)->toBeTrue()
        ->and($rule->is_active)->toBeTrue()
        ->and($rule->order)->toBe(1)
        ->and($rule->property->id)->toBe($this->propertyA->id)
        ->and($rule->categoryEnum())->toBe(PropertyRuleCategory::JamMalam);

    // Verify property relation
    expect($this->propertyA->rules->pluck('id'))->toContain($rule->id);
});

test('property rule active and ordered scopes filter correctly', function () {
    PropertyRule::where('property_id', $this->propertyA->id)->delete();

    $activeRule2 = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Tamu Menginap',
        'category' => PropertyRuleCategory::Tamu->value,
        'content' => 'Tamu lawan jenis dilarang menginap di kamar.',
        'is_active' => true,
        'order' => 2,
    ]);

    $activeRule1 = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Kebersihan Kamar',
        'category' => PropertyRuleCategory::Kebersihan->value,
        'content' => 'Jaga kebersihan kamar masing-masing.',
        'is_active' => true,
        'order' => 1,
    ]);

    $inactiveRule = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Lama Kadaluarsa',
        'category' => PropertyRuleCategory::Umum->value,
        'content' => 'Aturan lama yang sudah tidak berlaku.',
        'is_active' => false,
        'order' => 3,
    ]);

    $activeRules = PropertyRule::where('property_id', $this->propertyA->id)->active()->ordered()->get();

    expect($activeRules)->toHaveCount(2)
        ->and($activeRules->first()->id)->toBe($activeRule1->id)
        ->and($activeRules->last()->id)->toBe($activeRule2->id)
        ->and($activeRules->pluck('id'))->not->toContain($inactiveRule->id);
});

/*
|--------------------------------------------------------------------------
| Section 3: PropertyFaq Model & Non-Unique Question Verification
|--------------------------------------------------------------------------
*/

test('property faq can be created, supports identical questions across properties', function () {
    // Both properties can have the identical question with their own property-specific answers
    $faqA = PropertyFaq::create([
        'property_id' => $this->propertyA->id,
        'question' => 'Berapa password Wi-Fi kos?',
        'answer' => 'Password Wi-Fi Kos Alpha: harmoni2026',
        'category' => 'internet',
        'is_active' => true,
        'order' => 1,
    ]);

    $faqB = PropertyFaq::create([
        'property_id' => $this->propertyB->id,
        'question' => 'Berapa password Wi-Fi kos?',
        'answer' => 'Password Wi-Fi Kos Beta: beta_wifi_oke',
        'category' => 'internet',
        'is_active' => true,
        'order' => 1,
    ]);

    expect($faqA->exists)->toBeTrue()
        ->and($faqB->exists)->toBeTrue()
        ->and($faqA->question)->toBe($faqB->question)
        ->and($faqA->property_id)->not->toBe($faqB->property_id);

    expect($this->propertyA->faqs->pluck('id'))->toContain($faqA->id);
    expect($this->propertyB->faqs->pluck('id'))->toContain($faqB->id);
});

/*
|--------------------------------------------------------------------------
| Section 4: BillRiskScore Model, Integrity & Constraints
|--------------------------------------------------------------------------
*/

test('bill risk score can be created and cast attributes accurately', function () {
    BillRiskScore::where('bill_id', $this->billA->id)->delete();

    $riskScore = BillRiskScore::create([
        'bill_id' => $this->billA->id,
        'risk_score' => 78.50,
        'risk_tier' => DelinquencyRiskTier::Tinggi,
        'evaluator_type' => 'heuristic',
        'model_version' => '1.0.0',
        'contributing_factors' => [
            'Riwayat 2 kali terlambat bayar pada 4 bulan terakhir.',
            'Terdapat 1 tiket komplain fasilitas yang belum selesai.',
        ],
        'metadata' => [
            'confidence' => 0.85,
            'grace_period_days' => 3,
        ],
        'evaluated_at' => now(),
    ]);

    expect($riskScore->exists)->toBeTrue()
        ->and((float) $riskScore->risk_score)->toBe(78.50)
        ->and($riskScore->risk_tier)->toBe(DelinquencyRiskTier::Tinggi)
        ->and($riskScore->risk_tier->value)->toBe('tinggi')
        ->and($riskScore->riskTierEnum())->toBe(DelinquencyRiskTier::Tinggi)
        ->and($riskScore->isHighRisk())->toBeTrue()
        ->and($riskScore->isLowRisk())->toBeFalse()
        ->and($riskScore->badgeVariant())->toBe('danger')
        ->and($riskScore->tierLabel())->toBe('Risiko Tinggi')
        ->and($riskScore->contributing_factors)->toBeArray()
        ->and($riskScore->contributing_factors)->toHaveCount(2)
        ->and($riskScore->metadata['confidence'])->toBe(0.85);

    // Verify Bill relation
    expect($this->billA->riskScore)->not->toBeNull()
        ->and($this->billA->riskScore->id)->toBe($riskScore->id)
        ->and($riskScore->bill->id)->toBe($this->billA->id);
});

test('bill risk score enforces unique constraint on bill_id preventing conflicting active scores', function () {
    BillRiskScore::where('bill_id', $this->billA->id)->delete();

    BillRiskScore::create([
        'bill_id' => $this->billA->id,
        'risk_score' => 25.00,
        'risk_tier' => DelinquencyRiskTier::Rendah,
        'evaluator_type' => 'heuristic',
        'model_version' => '1.0.0',
        'contributing_factors' => ['Pembayaran selalu tepat waktu.'],
        'evaluated_at' => now(),
    ]);

    // Inserting a second score for the exact same bill_id must throw QueryException
    expect(fn () => BillRiskScore::create([
        'bill_id' => $this->billA->id,
        'risk_score' => 80.00,
        'risk_tier' => DelinquencyRiskTier::Tinggi,
        'evaluator_type' => 'heuristic',
        'model_version' => '1.0.0',
        'contributing_factors' => ['Konflik skor ganda.'],
        'evaluated_at' => now(),
    ]))->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Section 5: AiChatLog Model & Data Protection
|--------------------------------------------------------------------------
*/

test('ai chat log records conversation turn without sensitive credential data', function () {
    $chatLog = AiChatLog::create([
        'user_id' => $this->tenant->id,
        'property_id' => $this->propertyA->id,
        'session_id' => 'sess_test_123',
        'question' => 'Kapan gerbang kos ditutup?',
        'answer' => 'Pintu gerbang ditutup pukul 23:00 WIB.',
        'sources' => [
            ['id' => 1, 'title' => 'Jam Malam', 'score' => 0.92],
        ],
        'is_fallback' => false,
        'relevance_score' => 0.920,
        'processing_time_ms' => 185,
        'metadata' => [
            'tokens_in' => 12,
            'tokens_out' => 15,
        ],
    ]);

    expect($chatLog->exists)->toBeTrue()
        ->and($chatLog->is_fallback)->toBeFalse()
        ->and($chatLog->sources)->toBeArray()
        ->and($chatLog->relevance_score)->toBe('0.920')
        ->and($chatLog->processing_time_ms)->toBe(185)
        ->and($chatLog->user->id)->toBe($this->tenant->id)
        ->and($chatLog->property->id)->toBe($this->propertyA->id);

    // Verify scopes
    expect(AiChatLog::forProperty($this->propertyA->id)->pluck('id'))->toContain($chatLog->id);
    expect(AiChatLog::forUser($this->tenant->id)->pluck('id'))->toContain($chatLog->id);
    expect(AiChatLog::fallback()->pluck('id'))->not->toContain($chatLog->id);
});

test('ai chat log user and property foreign keys are set to null upon deletion without cascade deleting log history', function () {
    $tempUser = User::create([
        'name' => 'Temp Tenant For Log',
        'email' => 'temp_tenant_log@huniapp.test',
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $log = AiChatLog::create([
        'user_id' => $tempUser->id,
        'property_id' => $this->propertyA->id,
        'question' => 'Tanya fasilitas laundry',
        'answer' => 'Tersedia di lantai 2.',
    ]);

    expect($log->user_id)->toBe($tempUser->id);

    // Hard delete user
    $tempUser->delete();

    $refreshedLog = $log->fresh();
    expect($refreshedLog)->not->toBeNull()
        ->and($refreshedLog->user_id)->toBeNull()
        ->and($refreshedLog->question)->toBe('Tanya fasilitas laundry');
});

/*
|--------------------------------------------------------------------------
| Section 6: Policy and Authorization Enforcement
|--------------------------------------------------------------------------
*/

test('property rule policy enforces strict owner property isolation', function () {
    $ruleA = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Alpha',
        'content' => 'Aturan untuk kos Alpha.',
        'is_active' => true,
    ]);

    // Owner A owns property A -> can view, update, delete
    expect(Gate::forUser($this->ownerA)->allows('view', $ruleA))->toBeTrue();
    expect(Gate::forUser($this->ownerA)->allows('update', $ruleA))->toBeTrue();
    expect(Gate::forUser($this->ownerA)->allows('delete', $ruleA))->toBeTrue();

    // Owner B owns property B, NOT property A -> MUST BE DENIED
    expect(Gate::forUser($this->ownerB)->allows('view', $ruleA))->toBeFalse();
    expect(Gate::forUser($this->ownerB)->allows('update', $ruleA))->toBeFalse();
    expect(Gate::forUser($this->ownerB)->allows('delete', $ruleA))->toBeFalse();

    // Tenant CANNOT create, update, or delete rules
    expect(Gate::forUser($this->tenant)->allows('create', [PropertyRule::class, $this->propertyA]))->toBeFalse();
    expect(Gate::forUser($this->tenant)->allows('update', $ruleA))->toBeFalse();
    expect(Gate::forUser($this->tenant)->allows('delete', $ruleA))->toBeFalse();

    // Tenant resides in property A (active contract) -> CAN view active rule
    expect(Gate::forUser($this->tenant)->allows('view', $ruleA))->toBeTrue();

    // If rule is inactive, Tenant CANNOT view it
    $ruleA->update(['is_active' => false]);
    expect(Gate::forUser($this->tenant)->allows('view', $ruleA->fresh()))->toBeFalse();

    // Tenant DOES NOT reside in property B -> CANNOT view rules of property B
    $ruleB = PropertyRule::create([
        'property_id' => $this->propertyB->id,
        'title' => 'Aturan Beta',
        'content' => 'Aturan untuk kos Beta.',
        'is_active' => true,
    ]);
    expect(Gate::forUser($this->tenant)->allows('view', $ruleB))->toBeFalse();
});

test('property faq policy enforces owner isolation and tenant read-only boundary', function () {
    $faqA = PropertyFaq::create([
        'property_id' => $this->propertyA->id,
        'question' => 'Apakah boleh membawa hewan peliharaan?',
        'answer' => 'Tidak diperkenankan membawa hewan peliharaan jenis apa pun.',
        'is_active' => true,
    ]);

    // Owner A can manage
    expect(Gate::forUser($this->ownerA)->allows('view', $faqA))->toBeTrue();
    expect(Gate::forUser($this->ownerA)->allows('update', $faqA))->toBeTrue();
    expect(Gate::forUser($this->ownerA)->allows('delete', $faqA))->toBeTrue();

    // Owner B CANNOT manage
    expect(Gate::forUser($this->ownerB)->allows('view', $faqA))->toBeFalse();
    expect(Gate::forUser($this->ownerB)->allows('update', $faqA))->toBeFalse();
    expect(Gate::forUser($this->ownerB)->allows('delete', $faqA))->toBeFalse();

    // Tenant CANNOT create, update, delete
    expect(Gate::forUser($this->tenant)->allows('create', [PropertyFaq::class, $this->propertyA]))->toBeFalse();
    expect(Gate::forUser($this->tenant)->allows('update', $faqA))->toBeFalse();
    expect(Gate::forUser($this->tenant)->allows('delete', $faqA))->toBeFalse();

    // Tenant resides in property A -> CAN view active FAQ
    expect(Gate::forUser($this->tenant)->allows('view', $faqA))->toBeTrue();

    // Inactive FAQ -> Tenant denied
    $faqA->update(['is_active' => false]);
    expect(Gate::forUser($this->tenant)->allows('view', $faqA->fresh()))->toBeFalse();
});

test('bill risk score policy restricts access exclusively to property owner', function () {
    BillRiskScore::where('bill_id', $this->billA->id)->delete();

    $riskScore = BillRiskScore::create([
        'bill_id' => $this->billA->id,
        'risk_score' => 45.00,
        'risk_tier' => DelinquencyRiskTier::Sedang,
        'evaluator_type' => 'heuristic',
        'model_version' => '1.0.0',
        'contributing_factors' => ['Penyewa baru tanpa histori terlambat.'],
        'evaluated_at' => now(),
    ]);

    // Owner of the bill property (Owner A) CAN view
    expect(Gate::forUser($this->ownerA)->allows('view', $riskScore))->toBeTrue();

    // Another Owner (Owner B) CANNOT view
    expect(Gate::forUser($this->ownerB)->allows('view', $riskScore))->toBeFalse();

    // Tenant who is billed CANNOT view risk score (internal owner assessment)
    expect(Gate::forUser($this->tenant)->allows('view', $riskScore))->toBeFalse();
});

test('ai chat log policy allows owner to inspect their property logs and tenant only their own logs', function () {
    $log = AiChatLog::create([
        'user_id' => $this->tenant->id,
        'property_id' => $this->propertyA->id,
        'question' => 'Kapan jam malam?',
        'answer' => 'Pukul 23:00 WIB.',
    ]);

    // Owner A owns property A -> CAN view
    expect(Gate::forUser($this->ownerA)->allows('view', $log))->toBeTrue();

    // Owner B does NOT own property A -> CANNOT view
    expect(Gate::forUser($this->ownerB)->allows('view', $log))->toBeFalse();

    // Tenant who asked -> CAN view their own log
    expect(Gate::forUser($this->tenant)->allows('view', $log))->toBeTrue();

    // Other tenant -> CANNOT view
    $otherTenant = User::firstOrCreate(
        ['email' => 'other_tenant_phase11@huniapp.test'],
        ['name' => 'Other Tenant', 'role' => UserRole::Tenant, 'password' => Hash::make('password')]
    );
    expect(Gate::forUser($otherTenant)->allows('view', $log))->toBeFalse();
});
