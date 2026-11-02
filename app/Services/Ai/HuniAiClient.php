<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HuniAiClient
{
    protected string $baseUrl;

    protected string $secret;

    protected int $timeout;

    public function __construct(
        ?string $baseUrl = null,
        ?string $secret = null,
        ?int $timeout = null
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.ai.base_url', 'http://127.0.0.1:8001'), '/');
        $this->secret = $secret ?? (string) config('services.ai.secret', '');
        $this->timeout = $timeout ?? (int) config('services.ai.timeout', 5);
    }

    /**
     * Check if the client has a configured secret and valid base URL.
     */
    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->secret !== '';
    }

    /**
     * Public healthcheck of the AI service.
     *
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'data' => null,
                'error' => "Healthcheck failed with status {$response->status()}",
            ];
        } catch (ConnectionException $e) {
            Log::warning('HUNI AI healthcheck connection failed or timed out: '.$e->getMessage());

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Connection timed out or service unreachable',
            ];
        } catch (Throwable $e) {
            Log::error('HUNI AI healthcheck unexpected failure: '.$e->getMessage());

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Unexpected error connecting to AI service',
            ];
        }
    }

    /**
     * Synchronize a batch of property rules and FAQs to the AI service.
     *
     * @param  array<int, array{
     *     id: int,
     *     source_type: string,
     *     title: string,
     *     content: string,
     *     category?: string|null,
     *     is_active: bool,
     *     version: string
     * }>  $documents
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    public function syncKnowledge(int $propertyId, array $documents, string $mode = 'snapshot'): array
    {
        if ($propertyId <= 0) {
            return [
                'success' => false,
                'status' => 422,
                'data' => null,
                'error' => 'Invalid property_id. Must be a positive integer.',
            ];
        }

        if (! $this->isConfigured()) {
            Log::warning('HUNI AI client: AI service secret is not configured.');

            return [
                'success' => false,
                'status' => 401,
                'data' => null,
                'error' => 'AI service secret is not configured',
            ];
        }

        $payload = [
            'property_id' => $propertyId,
            'mode' => $mode,
            'documents' => $documents,
        ];

        return $this->sendAuthorizedRequest('POST', '/api/v1/rag/sync', $payload);
    }

    /**
     * Get property indexing status from AI service.
     *
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    public function getPropertyIndexStatus(int $propertyId): array
    {
        if ($propertyId <= 0) {
            return [
                'success' => false,
                'status' => 422,
                'data' => null,
                'error' => 'Invalid property_id. Must be a positive integer.',
            ];
        }

        if (! $this->isConfigured()) {
            Log::warning('HUNI AI client: AI service secret is not configured.');

            return [
                'success' => false,
                'status' => 401,
                'data' => null,
                'error' => 'AI service secret is not configured',
            ];
        }

        return $this->sendAuthorizedRequest('GET', "/api/v1/rag/properties/{$propertyId}/status", []);
    }

    /**
     * Query knowledge base for a specific property.
     *
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    public function queryKnowledge(int $propertyId, string $question, int $topK = 3): array
    {
        if ($propertyId <= 0) {
            return [
                'success' => false,
                'status' => 422,
                'data' => null,
                'error' => 'Invalid property_id. Must be a positive integer.',
            ];
        }

        if (! $this->isConfigured()) {
            Log::warning('HUNI AI client: AI service secret is not configured.');

            return [
                'success' => false,
                'status' => 401,
                'data' => null,
                'error' => 'AI service secret is not configured',
            ];
        }

        $payload = [
            'property_id' => $propertyId,
            'question' => $question,
            'top_k' => $topK,
        ];

        return $this->sendAuthorizedRequest('POST', '/api/v1/rag/query', $payload);
    }

    /**
     * Evaluate delinquency risk for a bill through the AI microservice.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    public function evaluateRisk(array $payload): array
    {
        if (! $this->isConfigured()) {
            Log::warning('HUNI AI client: AI service secret is not configured.');

            return [
                'success' => false,
                'status' => 401,
                'data' => null,
                'error' => 'AI service secret is not configured',
            ];
        }

        return $this->sendAuthorizedRequest('POST', '/api/v1/risk/evaluate', $payload);
    }

    /**
     * Send an authorized request with X-HUNI-AI-SECRET header and strict error handling.
     * Never logs the secret header or full request dump containing secrets.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, status: int, data: array<string, mixed>|null, error: string|null}
     */
    protected function sendAuthorizedRequest(string $method, string $path, array $payload): array
    {
        $url = "{$this->baseUrl}{$path}";

        try {
            $options = [];
            if ($method === 'GET') {
                if (! empty($payload)) {
                    $options['query'] = $payload;
                }
            } else {
                $options['json'] = $payload;
            }

            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-HUNI-AI-SECRET' => $this->secret,
                ])
                ->acceptJson()
                ->send($method, $url, $options);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'error' => null,
                ];
            }

            $statusCode = $response->status();

            if ($statusCode === 401) {
                Log::error("HUNI AI unauthorized (HTTP 401) at {$path}. Verify service secret.");

                return [
                    'success' => false,
                    'status' => 401,
                    'data' => null,
                    'error' => 'Unauthorized: Invalid or missing service secret',
                ];
            }

            if ($statusCode === 422) {
                return [
                    'success' => false,
                    'status' => 422,
                    'data' => $response->json(),
                    'error' => 'Payload validation error from AI service',
                ];
            }

            if ($statusCode >= 500) {
                Log::error("HUNI AI server error (HTTP {$statusCode}) at {$path}");

                return [
                    'success' => false,
                    'status' => $statusCode,
                    'data' => null,
                    'error' => "AI service error (HTTP {$statusCode})",
                ];
            }

            return [
                'success' => false,
                'status' => $statusCode,
                'data' => $response->json(),
                'error' => "AI service returned status {$statusCode}",
            ];
        } catch (ConnectionException $e) {
            Log::error("HUNI AI connection timeout or unreachable at {$path}: ".$e->getMessage());

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Connection to AI service failed or timed out',
            ];
        } catch (Throwable $e) {
            Log::error("HUNI AI unexpected exception at {$path}: ".$e->getMessage());

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Unexpected error interacting with AI service',
            ];
        }
    }
}
