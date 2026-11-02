<?php

declare(strict_types=1);

use App\Services\Ai\HuniAiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.ai.base_url', 'http://127.0.0.1:8001');
    Config::set('services.ai.secret', 'test_secret_for_pest_testing_123');
    Config::set('services.ai.timeout', 3);
});

test('healthCheck returns successful status when service is up', function () {
    Http::fake([
        'http://127.0.0.1:8001/health' => Http::response([
            'status' => 'ok',
            'service' => 'huni-ai-service',
            'version' => '0.1.0',
        ], 200),
    ]);

    $client = new HuniAiClient;
    $result = $client->healthCheck();

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200)
        ->and($result['data']['status'])->toBe('ok')
        ->and($result['data']['service'])->toBe('huni-ai-service')
        ->and($result['error'])->toBeNull();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'http://127.0.0.1:8001/health';
    });
});

test('healthCheck handles timeout or connection failure gracefully', function () {
    Http::fake([
        'http://127.0.0.1:8001/health' => function () {
            throw new ConnectionException('Connection timed out after 3000ms');
        },
    ]);

    $client = new HuniAiClient;
    $result = $client->healthCheck();

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(0)
        ->and($result['data'])->toBeNull()
        ->and($result['error'])->toContain('timed out');
});

test('syncKnowledge sends authorized POST request with X-HUNI-AI-SECRET', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response([
            'status' => 'success',
            'property_id' => 10,
            'summary' => [
                'total_received' => 2,
                'created' => 2,
                'updated' => 0,
                'deactivated' => 0,
                'deleted' => 0,
                'skipped' => 0,
            ],
            'timestamp' => '2026-10-19T02:00:00Z',
        ], 200),
    ]);

    $documents = [
        [
            'id' => 1,
            'source_type' => 'rule',
            'title' => 'Jam Malam',
            'content' => 'Pintu gerbang ditutup pukul 23:00 WIB',
            'category' => 'general',
            'is_active' => true,
            'version' => '1.0',
        ],
        [
            'id' => 2,
            'source_type' => 'faq',
            'title' => 'Parkir Motor',
            'content' => 'Parkir motor gratis di basement',
            'category' => null,
            'is_active' => true,
            'version' => '1.0',
        ],
    ];

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(10, $documents);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200)
        ->and($result['data']['summary']['created'])->toBe(2)
        ->and($result['data']['property_id'])->toBe(10);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'http://127.0.0.1:8001/api/v1/rag/sync'
            && $request->hasHeader('X-HUNI-AI-SECRET', 'test_secret_for_pest_testing_123')
            && $request['property_id'] === 10
            && $request['mode'] === 'snapshot'
            && count($request['documents']) === 2;
    });
});

test('syncKnowledge rejects invalid property_id before sending request', function () {
    Http::fake();

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(-1, []);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(422)
        ->and($result['error'])->toContain('Invalid property_id');

    Http::assertNothingSent();
});

test('syncKnowledge fails cleanly when secret is not configured', function () {
    Config::set('services.ai.secret', '');
    Http::fake();

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(10, []);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(401)
        ->and($result['error'])->toContain('not configured');

    Http::assertNothingSent();
});

test('syncKnowledge handles HTTP 401 unauthorized from service', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response([
            'detail' => 'Invalid or missing service secret',
        ], 401),
    ]);

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(10, []);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(401)
        ->and($result['error'])->toContain('Unauthorized');
});

test('syncKnowledge handles HTTP 500 internal server error gracefully', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => Http::response([
            'detail' => 'Internal server error',
        ], 500),
    ]);

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(10, []);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(500)
        ->and($result['error'])->toContain('HTTP 500');
});

test('syncKnowledge handles timeout and connection exceptions without throwing unhandled errors', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/sync' => function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        },
    ]);

    $client = new HuniAiClient;
    $result = $client->syncKnowledge(10, []);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(0)
        ->and($result['error'])->toContain('failed or timed out');
});

test('queryKnowledge returns honest fallback response from AI service', function () {
    Http::fake([
        'http://127.0.0.1:8001/api/v1/rag/query' => Http::response([
            'property_id' => 5,
            'question' => 'Apakah boleh bawa hewan peliharaan?',
            'retrieval_status' => 'semantic_search_unavailable',
            'is_fallback' => true,
            'message' => 'Semantic RAG retrieval is not available in Phase 11C. Vector store is in-memory only.',
            'total_indexed_documents' => 3,
            'matches' => [],
        ], 200),
    ]);

    $client = new HuniAiClient;
    $result = $client->queryKnowledge(5, 'Apakah boleh bawa hewan peliharaan?');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200)
        ->and($result['data']['is_fallback'])->toBeTrue()
        ->and($result['data']['retrieval_status'])->toBe('semantic_search_unavailable')
        ->and($result['data']['matches'])->toBeEmpty();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'http://127.0.0.1:8001/api/v1/rag/query'
            && $request['property_id'] === 5
            && $request['question'] === 'Apakah boleh bawa hewan peliharaan?'
            && $request['top_k'] === 3;
    });
});

test('queryKnowledge validates property_id before sending request', function () {
    Http::fake();

    $client = new HuniAiClient;
    $result = $client->queryKnowledge(0, 'Pertanyaan?');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(422)
        ->and($result['error'])->toContain('Invalid property_id');

    Http::assertNothingSent();
});
