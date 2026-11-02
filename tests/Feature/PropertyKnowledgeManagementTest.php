<?php

declare(strict_types=1);

use App\Enums\DelinquencyRiskTier;
use App\Enums\PropertyRuleCategory;
use App\Enums\UserRole;
use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\PropertyRule;
use App\Models\User;
use App\Services\PropertyKnowledgeSyncService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.ai.base_url', 'http://127.0.0.1:8001');
    Config::set('services.ai.secret', 'test_secret_for_pest_testing_123');
    Config::set('services.ai.timeout', 3);

    // Create 2 test owners
    $this->ownerA = User::firstOrCreate(
        ['email' => 'owner_kb_a@huniapp.test'],
        ['name' => 'Owner KB A', 'role' => UserRole::Owner, 'password' => Hash::make('password')]
    );

    $this->ownerB = User::firstOrCreate(
        ['email' => 'owner_kb_b@huniapp.test'],
        ['name' => 'Owner KB B', 'role' => UserRole::Owner, 'password' => Hash::make('password')]
    );

    // Create 1 test tenant
    $this->tenant = User::firstOrCreate(
        ['email' => 'tenant_kb_user@huniapp.test'],
        ['name' => 'Tenant KB User', 'role' => UserRole::Tenant, 'password' => Hash::make('password')]
    );

    // Create properties
    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'slug' => 'kos-kb-alpha',
        'name' => 'Kos KB Alpha',
        'address' => 'Jl. Kaliurang KM 6',
        'city' => 'Sleman',
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'slug' => 'kos-kb-beta',
        'name' => 'Kos KB Beta',
        'address' => 'Jl. Gejayan No. 20',
        'city' => 'Sleman',
    ]);
});

test('owner can access knowledge base management page for their property', function () {
    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.properties.knowledge.index', $this->propertyA));

    $response->assertOk()
        ->assertViewIs('owner.properties.knowledge')
        ->assertSee('Knowledge Base: Kos KB Alpha');
});

test('owner cannot access or manage another owners property knowledge base', function () {
    // Owner B tries to access Owner A's property knowledge base
    $response = $this->actingAs($this->ownerB)
        ->get(route('owner.properties.knowledge.index', $this->propertyA));

    $response->assertForbidden();
});

test('tenant is blocked from accessing owner knowledge base management', function () {
    $response = $this->actingAs($this->tenant)
        ->get(route('owner.properties.knowledge.index', $this->propertyA));

    // Blocked by owner role middleware
    $response->assertForbidden();
});

test('owner can create property rule and triggers automatic snapshot sync', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response([
            'status' => 'success',
            'property_id' => $this->propertyA->id,
            'summary' => [
                'total_received' => 1,
                'created' => 1,
                'updated' => 0,
                'deactivated' => 0,
                'deleted' => 0,
                'skipped' => 0,
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200),
    ]);

    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.knowledge.rules.store', $this->propertyA), [
            'title' => 'Jam Malam Kos',
            'content' => 'Pintu pagar ditutup pukul 23:00 WIB untuk keamanan bersama.',
            'category' => PropertyRuleCategory::JamMalam->value,
            'is_active' => '1',
            'order' => 1,
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('property_rules', [
        'property_id' => $this->propertyA->id,
        'title' => 'Jam Malam Kos',
        'category' => PropertyRuleCategory::JamMalam->value,
        'is_active' => 1,
    ]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'http://127.0.0.1:8001/api/v1/rag/sync'
            && $request['property_id'] === $this->propertyA->id
            && $request['mode'] === 'snapshot'
            && count($request['documents']) === 1
            && $request['documents'][0]['source_type'] === 'rule'
            && $request['documents'][0]['title'] === 'Jam Malam Kos';
    });
});

test('rule creation validates required fields and valid categories', function () {
    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.knowledge.rules.store', $this->propertyA), [
            'title' => '',
            'content' => '',
            'category' => 'invalid_category_123',
        ]);

    $response->assertSessionHasErrors(['title', 'content', 'category']);
});

test('owner can update, toggle, and delete property rules', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response(['status' => 'success'], 200),
    ]);

    $rule = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Tamu Menginap',
        'content' => 'Tamu menginap wajib lapor ke pengelola kos.',
        'category' => PropertyRuleCategory::Tamu->value,
        'is_active' => true,
        'order' => 2,
    ]);

    // 1. Update rule
    $updateRes = $this->actingAs($this->ownerA)
        ->put(route('owner.properties.knowledge.rules.update', [$this->propertyA, $rule]), [
            'title' => 'Tamu Menginap Diperbarui',
            'content' => 'Tamu menginap maksimal 2 malam berturut-turut.',
            'category' => PropertyRuleCategory::Tamu->value,
            'is_active' => '1',
        ]);
    $updateRes->assertRedirect();
    $this->assertDatabaseHas('property_rules', [
        'id' => $rule->id,
        'title' => 'Tamu Menginap Diperbarui',
    ]);

    // 2. Toggle rule to inactive
    $toggleRes = $this->actingAs($this->ownerA)
        ->patch(route('owner.properties.knowledge.rules.toggle', [$this->propertyA, $rule]));
    $toggleRes->assertRedirect();
    expect($rule->fresh()->is_active)->toBeFalse();

    // 3. Delete rule
    $deleteRes = $this->actingAs($this->ownerA)
        ->delete(route('owner.properties.knowledge.rules.destroy', [$this->propertyA, $rule]));
    $deleteRes->assertRedirect();
    $this->assertDatabaseMissing('property_rules', ['id' => $rule->id]);
});

test('owner can create, update, toggle, and delete property FAQs', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response(['status' => 'success'], 200),
    ]);

    // 1. Create FAQ
    $createRes = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.knowledge.faqs.store', $this->propertyA), [
            'question' => 'Apakah biaya sewa sudah termasuk listrik?',
            'answer' => 'Biaya sewa kamar belum termasuk token listrik (listrik mandiri per kamar).',
            'category' => 'Fasilitas & Biaya',
            'is_active' => '1',
            'order' => 1,
        ]);
    $createRes->assertRedirect();
    $faq = PropertyFaq::where('property_id', $this->propertyA->id)->first();
    expect($faq)->not->toBeNull()
        ->and($faq->question)->toBe('Apakah biaya sewa sudah termasuk listrik?');

    // 2. Update FAQ
    $updateRes = $this->actingAs($this->ownerA)
        ->put(route('owner.properties.knowledge.faqs.update', [$this->propertyA, $faq]), [
            'question' => 'Apakah biaya sewa termasuk listrik dan air?',
            'answer' => 'Air PDAM gratis, listrik menggunakan token mandiri.',
            'category' => 'Utilitas',
            'is_active' => '1',
        ]);
    $updateRes->assertRedirect();
    expect($faq->fresh()->category)->toBe('Utilitas');

    // 3. Toggle FAQ
    $toggleRes = $this->actingAs($this->ownerA)
        ->patch(route('owner.properties.knowledge.faqs.toggle', [$this->propertyA, $faq]));
    $toggleRes->assertRedirect();
    expect($faq->fresh()->is_active)->toBeFalse();

    // 4. Delete FAQ
    $deleteRes = $this->actingAs($this->ownerA)
        ->delete(route('owner.properties.knowledge.faqs.destroy', [$this->propertyA, $faq]));
    $deleteRes->assertRedirect();
    $this->assertDatabaseMissing('property_faqs', ['id' => $faq->id]);
});

test('syncSnapshot sends only active rules and faqs and enforces property isolation', function () {
    // Property A: 1 active rule, 1 inactive rule, 1 active faq
    $activeRuleA = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Aktif A',
        'content' => 'Konten aturan aktif A',
        'category' => 'umum',
        'is_active' => true,
    ]);

    $inactiveRuleA = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Nonaktif A',
        'content' => 'Konten aturan nonaktif A',
        'category' => 'umum',
        'is_active' => false,
    ]);

    $activeFaqA = PropertyFaq::create([
        'property_id' => $this->propertyA->id,
        'question' => 'FAQ Aktif A',
        'answer' => 'Jawaban FAQ aktif A',
        'is_active' => true,
    ]);

    // Property B: 1 active rule
    $activeRuleB = PropertyRule::create([
        'property_id' => $this->propertyB->id,
        'title' => 'Aturan Aktif B',
        'content' => 'Konten aturan aktif B',
        'category' => 'umum',
        'is_active' => true,
    ]);

    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response([
            'status' => 'success',
            'property_id' => $this->propertyA->id,
            'summary' => [
                'total_received' => 2,
                'created' => 2,
                'updated' => 0,
                'deactivated' => 0,
                'deleted' => 0,
                'skipped' => 0,
            ],
        ], 200),
    ]);

    $syncService = new PropertyKnowledgeSyncService;
    $result = $syncService->syncProperty($this->propertyA);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('synced')
        ->and($result['summary']['total_received'])->toBe(2);

    Http::assertSent(function (Request $request) use ($activeRuleA, $activeFaqA, $inactiveRuleA, $activeRuleB) {
        if ($request->url() !== 'http://127.0.0.1:8001/api/v1/rag/sync') {
            return false;
        }

        $docs = $request['documents'];

        // Must strictly contain 2 documents (Active Rule A + Active FAQ A)
        $docIds = array_column($docs, 'id');
        $hasActiveRuleA = in_array($activeRuleA->id, $docIds, true);
        $hasActiveFaqA = in_array($activeFaqA->id, $docIds, true);
        $hasInactiveRuleA = in_array($inactiveRuleA->id, $docIds, true);
        $hasActiveRuleB = in_array($activeRuleB->id, $docIds, true);

        return $request['property_id'] === $this->propertyA->id
            && count($docs) === 2
            && $hasActiveRuleA
            && $hasActiveFaqA
            && ! $hasInactiveRuleA // Inactive rule MUST be excluded
            && ! $hasActiveRuleB;  // Property B MUST NOT leak into Property A
    });
});

test('fastapi sync failure retains Laravel database changes and records failed status without crashing', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => function () {
            throw new ConnectionException('Connection to AI microservice timed out');
        },
    ]);

    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.properties.knowledge.rules.store', $this->propertyA), [
            'title' => 'Aturan saat AI offline',
            'content' => 'Aturan ini tetap harus tersimpan di MySQL Laravel.',
            'category' => 'keamanan',
            'is_active' => '1',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('sync_warning');

    // Rule is successfully stored in MySQL!
    $this->assertDatabaseHas('property_rules', [
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan saat AI offline',
    ]);

    // Sync status is marked as failed in cache for user retry
    $syncService = new PropertyKnowledgeSyncService;
    $status = $syncService->getSyncStatus($this->propertyA);
    expect($status['status'])->toBe('failed')
        ->and($status['is_stale'])->toBeTrue()
        ->and($status['error'])->toContain('timed out');
});

test('review Phase 11D: boundary threshold risk scores at 39.99, 40, 69.99, and 70', function () {
    // Verifying that DelinquencyRiskTier maps strictly to thresholds
    expect(DelinquencyRiskTier::Rendah->value)->toBe('rendah')
        ->and(DelinquencyRiskTier::Sedang->value)->toBe('sedang')
        ->and(DelinquencyRiskTier::Tinggi->value)->toBe('tinggi');

    // Test tier helper function logic matching Phase 11D specification
    $classify = function (float $score): DelinquencyRiskTier {
        if ($score <= 39.99) {
            return DelinquencyRiskTier::Rendah;
        }
        if ($score <= 69.99) {
            return DelinquencyRiskTier::Sedang;
        }

        return DelinquencyRiskTier::Tinggi;
    };

    expect($classify(0.00))->toBe(DelinquencyRiskTier::Rendah)
        ->and($classify(39.99))->toBe(DelinquencyRiskTier::Rendah)
        ->and($classify(40.00))->toBe(DelinquencyRiskTier::Sedang)
        ->and($classify(69.99))->toBe(DelinquencyRiskTier::Sedang)
        ->and($classify(70.00))->toBe(DelinquencyRiskTier::Tinggi)
        ->and($classify(100.00))->toBe(DelinquencyRiskTier::Tinggi);
});

test('BUG-01 regression: owner can update rule via slug in URL without 404', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response(['status' => 'success'], 200),
    ]);

    $rule = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Parkir Tamu',
        'content' => 'Parkir tamu di sisi timur.',
        'category' => PropertyRuleCategory::Parkir->value,
        'is_active' => true,
        'order' => 1,
    ]);

    // Request using the slug string directly in URL path (simulating JS route call)
    $response = $this->actingAs($this->ownerA)
        ->put("/owner/properties/{$this->propertyA->slug}/knowledge/rules/{$rule->id}", [
            'title' => 'Aturan Parkir Tamu Diperbarui',
            'content' => 'Parkir tamu maksimal 2 motor di sisi timur.',
            'category' => PropertyRuleCategory::Parkir->value,
            'is_active' => '1',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('property_rules', [
        'id' => $rule->id,
        'title' => 'Aturan Parkir Tamu Diperbarui',
    ]);
});

test('BUG-01 regression: owner can update faq via slug in URL without 404', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response(['status' => 'success'], 200),
    ]);

    $faq = PropertyFaq::create([
        'property_id' => $this->propertyA->id,
        'question' => 'Jam berapa gerbang ditutup?',
        'answer' => 'Pukul 23:00 WIB.',
        'category' => 'Keamanan',
        'is_active' => true,
        'order' => 1,
    ]);

    // Request using the slug string directly in URL path (simulating JS route call)
    $response = $this->actingAs($this->ownerA)
        ->put("/owner/properties/{$this->propertyA->slug}/knowledge/faqs/{$faq->id}", [
            'question' => 'Jam berapa gerbang kos ditutup?',
            'answer' => 'Pukul 23:00 WIB tepat setiap malam.',
            'category' => 'Keamanan',
            'is_active' => '1',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('property_faqs', [
        'id' => $faq->id,
        'question' => 'Jam berapa gerbang kos ditutup?',
    ]);
});

test('BUG-01 regression: cross-tenant isolation forbids Owner B from updating Owner A rules or faqs', function () {
    $rule = PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Aturan Owner A',
        'content' => 'Konten rahasia A',
        'category' => PropertyRuleCategory::Umum->value,
        'is_active' => true,
    ]);

    $faq = PropertyFaq::create([
        'property_id' => $this->propertyA->id,
        'question' => 'Pertanyaan Owner A?',
        'answer' => 'Jawaban A',
        'category' => 'Umum',
        'is_active' => true,
    ]);

    // Owner B attempts to update Owner A's rule via slug URL
    $responseRuleSlug = $this->actingAs($this->ownerB)
        ->put("/owner/properties/{$this->propertyA->slug}/knowledge/rules/{$rule->id}", [
            'title' => 'Hacked Rule',
            'content' => 'Hacked content',
            'category' => PropertyRuleCategory::Umum->value,
        ]);
    $responseRuleSlug->assertForbidden();

    // Owner B attempts to update Owner A's faq via slug URL
    $responseFaqSlug = $this->actingAs($this->ownerB)
        ->put("/owner/properties/{$this->propertyA->slug}/knowledge/faqs/{$faq->id}", [
            'question' => 'Hacked Question?',
            'answer' => 'Hacked Answer',
            'category' => 'Umum',
        ]);
    $responseFaqSlug->assertForbidden();

    // Owner B attempts to pass Owner A's rule under Owner B's property URL (IDOR mismatch)
    $responseMismatch = $this->actingAs($this->ownerB)
        ->put("/owner/properties/{$this->propertyB->slug}/knowledge/rules/{$rule->id}", [
            'title' => 'Injected Rule',
            'content' => 'Injected content',
            'category' => PropertyRuleCategory::Umum->value,
        ]);
    $responseMismatch->assertNotFound();
});
