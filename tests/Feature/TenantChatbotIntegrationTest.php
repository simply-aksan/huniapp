<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AiChatLog;
use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyRule;
use App\Models\Room;
use App\Models\User;
use App\Services\PropertyKnowledgeSyncService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Config::set('services.ai.base_url', 'http://127.0.0.1:8001');
    Config::set('services.ai.secret', 'test_secret_for_pest_testing_123');
    Config::set('services.ai.timeout', 3);

    // 1. Setup Owner
    $this->owner = User::create([
        'name' => 'Owner Kos Amanah',
        'email' => 'owner_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // 2. Setup Properties
    $this->propertyA = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kos Amanah Melati',
        'slug' => 'kos-amanah-melati-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Kaliurang KM 5',
        'city' => 'Sleman',
        'district' => 'Depok',
        'postal_code' => '55281',
        'total_rooms' => 10,
        'status' => 'aktif',
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kos Bintang Timur',
        'slug' => 'kos-bintang-timur-'.Str::random(8),
        'type' => 'putra',
        'address' => 'Jl. Gejayan No. 12',
        'city' => 'Sleman',
        'district' => 'Depok',
        'postal_code' => '55283',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    // 3. Setup Rooms
    $this->roomA = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => 'M-101',
        'type' => 'standar',
        'price' => 1000000,
        'deposit' => 300000,
        'status' => 'terisi',
    ]);

    $this->roomB = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => 'B-201',
        'type' => 'deluxe',
        'price' => 1500000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 4. Setup Tenants
    $this->tenantA = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->tenantB = User::create([
        'name' => 'Dewi Lestari',
        'email' => 'dewi_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // 5. Active Contract for Tenant A on Property A
    $this->contractA = Contract::create([
        'user_id' => $this->tenantA->id,
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA->id,
        'contract_number' => 'CTR-A-'.Str::random(6),
        'start_date' => Carbon::now()->subMonths(1),
        'end_date' => Carbon::now()->addMonths(5),
        'duration_months' => 6,
        'monthly_rent' => 1000000,
        'total_amount' => 6000000,
        'deposit_amount' => 300000,
        'status' => 'aktif',
    ]);
});

test('unauthenticated guest is redirected to login', function () {
    $response = $this->get(route('tenant.chat.index'));
    $response->assertRedirect(route('login'));
});

test('owner role cannot access tenant chat portal', function () {
    $response = $this->actingAs($this->owner)->get(route('tenant.chat.index'));
    $response->assertForbidden();
});

test('tenant without active contract sees empty contract state and cannot chat', function () {
    // Tenant B has no active contract
    $response = $this->actingAs($this->tenantB)->get(route('tenant.chat.index'));
    $response->assertOk();
    $response->assertSee('Belum Memiliki Kontrak Aktif');
    $response->assertSee('Fitur');

    // Attempting to post question returns 403 Forbidden
    $askResponse = $this->actingAs($this->tenantB)->postJson(route('tenant.chat.ask'), [
        'question' => 'Kapan jam malam kos?',
    ]);
    $askResponse->assertForbidden();
    $askResponse->assertJson([
        'success' => false,
        'message' => 'Akses ditolak. Anda tidak memiliki kontrak aktif pada kos mana pun.',
    ]);
});

test('tenant with active contract can view chat interface for their property', function () {
    Http::fake([
        '*/api/v1/rag/properties/*/status' => Http::response([
            'property_id' => $this->propertyA->id,
            'is_indexed' => true,
            'active_documents_count' => 2,
            'last_indexed_at' => now()->toIso8601String(),
        ], 200),
    ]);

    $response = $this->actingAs($this->tenantA)->get(route('tenant.chat.index'));
    $response->assertOk();
    $response->assertSee('Tanya HUNI AI');
    $response->assertSee($this->propertyA->name);
    $response->assertSee('Jawaban resmi berdasarkan tata tertib dan FAQ');
});

test('tenant cannot tamper property_id to query another property where they have no active contract', function () {
    // Tenant A only has active contract in Property A. Tampering with Property B must be rejected!
    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'property_id' => $this->propertyB->id,
        'question' => 'Apa aturan kos di sini?',
    ]);

    $response->assertForbidden();
    $response->assertJson([
        'success' => false,
        'message' => 'Akses ditolak. Anda tidak terdaftar pada properti yang diminta.',
    ]);

    // Verify no chat log was created for the tampered property
    expect(AiChatLog::where('property_id', $this->propertyB->id)->count())->toBe(0);
});

test('tenant asking question receives grounded retrieval and citations from FastAPI', function () {
    Http::fake([
        '*/api/v1/rag/query' => Http::response([
            'status' => 'ok',
            'property_id' => $this->propertyA->id,
            'question' => 'Jam berapa pintu gerbang kos ditutup?',
            'retrieval_status' => 'grounded_retrieval',
            'is_fallback' => false,
            'answer' => "Berikut informasi resmi yang relevan:\n\n1. [Aturan] Jam Malam\nPintu gerbang utama kos ditutup pukul 23:00 WIB.",
            'sources' => [
                [
                    'id' => '10',
                    'source_type' => 'rule',
                    'title' => 'Jam Malam',
                    'content' => 'Pintu gerbang utama kos ditutup pukul 23:00 WIB.',
                    'category' => 'ketertiban',
                    'relevance_score' => 0.85,
                ],
            ],
            'active_documents_count' => 5,
            'message' => 'Retrieved 1 relevant document(s)',
        ], 200),
    ]);

    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'question' => 'Jam berapa pintu gerbang kos ditutup?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'is_fallback' => false,
        'retrieval_status' => 'grounded_retrieval',
        'relevance_score' => 0.85,
    ]);
    $response->assertJsonPath('sources.0.title', 'Jam Malam');
    $response->assertJsonPath('sources.0.relevance_score', 0.85);

    // Verify logged to ai_chat_logs
    $log = AiChatLog::where('user_id', $this->tenantA->id)->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->property_id)->toBe($this->propertyA->id)
        ->and($log->question)->toBe('Jam berapa pintu gerbang kos ditutup?')
        ->and($log->is_fallback)->toBeFalse()
        ->and((float) $log->relevance_score)->toBe(0.85);
});

test('tenant receives honest fallback when inquiry has insufficient evidence', function () {
    Http::fake([
        '*/api/v1/rag/query' => Http::response([
            'status' => 'ok',
            'property_id' => $this->propertyA->id,
            'question' => 'Bagaimana cara memelihara burung unta di kos?',
            'retrieval_status' => 'insufficient_evidence',
            'is_fallback' => true,
            'answer' => 'Pertanyaan Anda tidak dapat dijawab berdasarkan aturan atau FAQ resmi kos yang tersedia. Silakan hubungi langsung pemilik kos.',
            'sources' => [],
            'active_documents_count' => 5,
            'message' => 'No documents exceeded relevance threshold',
        ], 200),
    ]);

    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'question' => 'Bagaimana cara memelihara burung unta di kos?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'is_fallback' => true,
        'retrieval_status' => 'insufficient_evidence',
        'sources' => [],
    ]);

    // Verify logged as fallback
    $log = AiChatLog::where('user_id', $this->tenantA->id)->latest('id')->first();
    expect($log->is_fallback)->toBeTrue()
        ->and($log->relevance_score)->toBe('0.000');
});

test('fastapi timeout or connection failure produces safe fallback without breaking portal', function () {
    Http::fake([
        '*/api/v1/rag/query' => function () {
            throw new ConnectionException('Connection timed out after 3000ms');
        },
    ]);

    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'question' => 'Kapan jam malam?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'is_fallback' => true,
        'retrieval_status' => 'service_unreachable',
    ]);
    expect($response->json('answer'))->toContain('Layanan asisten AI sedang tidak dapat dihubungi');

    // Verify logged with metadata
    $log = AiChatLog::where('user_id', $this->tenantA->id)->latest('id')->first();
    expect($log->is_fallback)->toBeTrue()
        ->and($log->metadata['error'])->toContain('timed out');
});

test('fastapi http 500 error produces safe fallback', function () {
    Http::fake([
        '*/api/v1/rag/query' => Http::response([
            'detail' => 'Internal server error',
        ], 500),
    ]);

    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'question' => 'Kapan jam malam?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'is_fallback' => true,
        'retrieval_status' => 'service_unreachable',
    ]);
});

test('tenant with multiple active contracts can choose property explicitly', function () {
    // Give Tenant A a second active contract on Property B
    Contract::create([
        'user_id' => $this->tenantA->id,
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB->id,
        'contract_number' => 'CTR-B-'.Str::random(6),
        'start_date' => Carbon::now()->subMonths(1),
        'end_date' => Carbon::now()->addMonths(5),
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'total_amount' => 9000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    Http::fake([
        '*/api/v1/rag/properties/*/status' => Http::response([
            'property_id' => $this->propertyB->id,
            'is_indexed' => true,
            'active_documents_count' => 1,
            'last_indexed_at' => now()->toIso8601String(),
        ], 200),
        '*/api/v1/rag/query' => Http::response([
            'status' => 'ok',
            'property_id' => $this->propertyB->id,
            'question' => 'Boleh bawa motor?',
            'retrieval_status' => 'grounded_retrieval',
            'is_fallback' => false,
            'answer' => 'Parkir motor gratis di Kos Bintang Timur.',
            'sources' => [],
            'active_documents_count' => 1,
            'message' => 'ok',
        ], 200),
    ]);

    // Switching property view
    $viewResponse = $this->actingAs($this->tenantA)->get(route('tenant.chat.index', ['property_id' => $this->propertyB->id]));
    $viewResponse->assertOk();
    $viewResponse->assertSee($this->propertyB->name);

    // Querying property B specifically
    $askResponse = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'property_id' => $this->propertyB->id,
        'question' => 'Boleh bawa motor?',
    ]);
    $askResponse->assertOk();

    // Verify logged under Property B
    $log = AiChatLog::where('user_id', $this->tenantA->id)->latest('id')->first();
    expect($log->property_id)->toBe($this->propertyB->id);
});

test('tenant can clear their conversation history for a specific property', function () {
    AiChatLog::create([
        'user_id' => $this->tenantA->id,
        'property_id' => $this->propertyA->id,
        'session_id' => 'sess_1',
        'question' => 'Pertanyaan 1',
        'answer' => 'Jawaban 1',
        'sources' => [],
        'is_fallback' => false,
        'relevance_score' => 0.8,
        'processing_time_ms' => 10,
    ]);

    expect(AiChatLog::where('user_id', $this->tenantA->id)->count())->toBe(1);

    $response = $this->actingAs($this->tenantA)->post(route('tenant.chat.clear'), [
        'property_id' => $this->propertyA->id,
    ]);

    $response->assertRedirect();
    expect(AiChatLog::where('user_id', $this->tenantA->id)->count())->toBe(0);
});

test('verifyOrRebuildIndex rebuilds index when FastAPI reports not indexed', function () {
    // Add rule and FAQ to property A
    PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Dilarang Merokok',
        'content' => 'Merokok di dalam kamar dikenakan denda Rp 200.000.',
        'is_active' => true,
        'order' => 1,
    ]);

    Http::fake([
        '*/api/v1/rag/properties/*/status' => Http::response([
            'property_id' => $this->propertyA->id,
            'is_indexed' => false,
            'active_documents_count' => 0,
            'last_indexed_at' => null,
        ], 200),
        '*/api/v1/rag/sync' => Http::response([
            'status' => 'success',
            'property_id' => $this->propertyA->id,
            'mode' => 'snapshot',
            'received' => 1,
            'updated' => 1,
            'removed_or_deactivated' => 0,
            'skipped' => 0,
            'total_active_for_property' => 1,
            'message' => 'Synced',
        ], 200),
    ]);

    /** @var PropertyKnowledgeSyncService $syncService */
    $syncService = app(PropertyKnowledgeSyncService::class);
    $result = $syncService->verifyOrRebuildIndex($this->propertyA);

    expect($result['ready'])->toBeTrue()
        ->and($result['rebuilt'])->toBeTrue();

    // Verify that syncKnowledge was called
    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/api/v1/rag/sync')
            && $request->header('X-HUNI-AI-SECRET')[0] === 'test_secret_for_pest_testing_123';
    });
});

test('tenant cannot clear history of a property where they have no active contract', function () {
    // Tenant A only has active contract on Property A, trying to clear Property B
    $response = $this->actingAs($this->tenantA)->post(route('tenant.chat.clear'), [
        'property_id' => $this->propertyB->id,
    ]);

    $response->assertForbidden();
});

test('when fastapi index is unready and rebuild fails, ask endpoint returns honest fallback', function () {
    Http::fake([
        '*/api/v1/rag/properties/*/status' => Http::response([], 500),
        '*/api/v1/rag/sync' => Http::response([], 500),
    ]);

    // Give property A a rule so index is expected
    PropertyRule::create([
        'property_id' => $this->propertyA->id,
        'title' => 'Tata Tertib',
        'content' => 'Wajib menjaga ketenangan.',
        'is_active' => true,
        'order' => 1,
    ]);

    $response = $this->actingAs($this->tenantA)->postJson(route('tenant.chat.ask'), [
        'question' => 'Bagaimana ketenangan kos?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'is_fallback' => true,
        'retrieval_status' => 'service_unreachable',
    ]);
});
