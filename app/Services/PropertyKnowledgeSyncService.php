<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\PropertyRule;
use App\Services\Ai\HuniAiClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PropertyKnowledgeSyncService
{
    public const CACHE_PREFIX = 'property_rag_sync_';

    public function __construct(
        protected ?HuniAiClient $aiClient = null
    ) {
        $this->aiClient = $aiClient ?? app(HuniAiClient::class);
    }

    /**
     * Synchronize a complete snapshot of active rules and FAQs for a single property.
     * Guaranteed to send only authorized, active documents belonging to this property.
     *
     * @return array{
     *     success: bool,
     *     status: string,
     *     message: string,
     *     summary: array<string, int>|null,
     *     error: string|null,
     *     synced_at: string|null,
     * }
     */
    public function syncProperty(Property $property): array
    {
        $cacheKey = self::CACHE_PREFIX.$property->id;

        // 1. Fetch only active rules and FAQs belonging strictly to this property
        $activeRules = $property->rules()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $activeFaqs = $property->faqs()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $documents = [];

        /** @var PropertyRule $rule */
        foreach ($activeRules as $rule) {
            $updatedTimestamp = $rule->updated_at ? $rule->updated_at->timestamp : $rule->created_at?->timestamp ?? time();
            $documents[] = [
                'id' => (int) $rule->id,
                'source_type' => 'rule',
                'title' => trim((string) $rule->title),
                'content' => trim((string) $rule->content),
                'category' => $rule->category ? (string) $rule->category : null,
                'is_active' => true,
                'version' => 'v-'.$updatedTimestamp,
            ];
        }

        /** @var PropertyFaq $faq */
        foreach ($activeFaqs as $faq) {
            $updatedTimestamp = $faq->updated_at ? $faq->updated_at->timestamp : $faq->created_at?->timestamp ?? time();
            $documents[] = [
                'id' => (int) $faq->id,
                'source_type' => 'faq',
                'title' => trim((string) $faq->question),
                'content' => trim((string) $faq->answer),
                'category' => $faq->category ? (string) $faq->category : null,
                'is_active' => true,
                'version' => 'v-'.$updatedTimestamp,
            ];
        }

        // 2. Dispatch full snapshot request to FastAPI
        $result = $this->aiClient->syncKnowledge($property->id, $documents, 'snapshot');

        if (! ($result['success'] ?? false)) {
            $errorMessage = $result['error'] ?? 'Gagal menghubungi service AI FastAPI';
            Log::warning("HUNI AI Knowledge Sync failed for Property #{$property->id}: {$errorMessage}");

            $syncData = [
                'status' => 'failed',
                'synced_at' => null,
                'attempted_at' => now()->toIso8601String(),
                'error' => $errorMessage,
                'total_documents' => count($documents),
                'summary' => null,
            ];

            Cache::put($cacheKey, $syncData, now()->addDays(7));

            return [
                'success' => false,
                'status' => 'failed',
                'message' => "Sinkronisasi AI gagal: {$errorMessage}",
                'summary' => null,
                'error' => $errorMessage,
                'synced_at' => null,
            ];
        }

        $summary = $result['data']['summary'] ?? [
            'total_received' => count($documents),
            'created' => count($documents),
            'updated' => 0,
            'deactivated' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        $nowIso = now()->toIso8601String();
        $syncData = [
            'status' => 'synced',
            'synced_at' => $nowIso,
            'attempted_at' => $nowIso,
            'error' => null,
            'total_documents' => count($documents),
            'summary' => $summary,
        ];

        Cache::put($cacheKey, $syncData, now()->addDays(7));

        return [
            'success' => true,
            'status' => 'synced',
            'message' => 'Knowledge base berhasil disinkronkan ke AI service.',
            'summary' => $summary,
            'error' => null,
            'synced_at' => $nowIso,
        ];
    }

    /**
     * Get the latest sync status metadata for a property.
     * Computes whether existing knowledge documents are stale compared to last sync,
     * and optionally verifies against actual FastAPI index status to prevent false 'synced'.
     *
     * @return array{
     *     status: 'synced'|'failed'|'unindexed'|'stale',
     *     synced_at: string|null,
     *     error: string|null,
     *     summary: array<string, int>|null,
     *     total_active_documents: int,
     *     is_stale: bool,
     * }
     */
    public function getSyncStatus(Property $property, bool $verifyWithAi = false): array
    {
        $cacheKey = self::CACHE_PREFIX.$property->id;
        $cached = Cache::get($cacheKey);

        $activeRulesCount = $property->rules()->where('is_active', true)->count();
        $activeFaqsCount = $property->faqs()->where('is_active', true)->count();
        $totalActive = $activeRulesCount + $activeFaqsCount;

        if (! is_array($cached) || ! isset($cached['status'])) {
            return [
                'status' => 'unindexed',
                'synced_at' => null,
                'error' => null,
                'summary' => null,
                'total_active_documents' => $totalActive,
                'is_stale' => $totalActive > 0,
            ];
        }

        if ($cached['status'] === 'failed') {
            return [
                'status' => 'failed',
                'synced_at' => null,
                'error' => (string) ($cached['error'] ?? 'Sinkronisasi gagal'),
                'summary' => null,
                'total_active_documents' => $totalActive,
                'is_stale' => true,
            ];
        }

        // Check if rules or faqs have been updated after last synced_at
        $syncedAt = isset($cached['synced_at']) ? Carbon::parse($cached['synced_at']) : null;
        $isStale = false;

        if ($syncedAt !== null) {
            $latestRuleUpdate = $property->rules()->max('updated_at');
            $latestFaqUpdate = $property->faqs()->max('updated_at');

            $latestUpdate = max(
                $latestRuleUpdate ? Carbon::parse($latestRuleUpdate) : Carbon::minValue(),
                $latestFaqUpdate ? Carbon::parse($latestFaqUpdate) : Carbon::minValue()
            );

            if ($latestUpdate->isAfter($syncedAt)) {
                $isStale = true;
            }
        } else {
            $isStale = true;
        }

        // If requested, cross-check against actual AI index status to prevent false 'synced'
        if ($verifyWithAi && ! $isStale) {
            $statusCheck = $this->aiClient->getPropertyIndexStatus($property->id);
            if ($statusCheck['success']) {
                $isIndexed = (bool) ($statusCheck['data']['is_indexed'] ?? false);
                $aiDocCount = (int) ($statusCheck['data']['active_documents_count'] ?? 0);
                if (! $isIndexed && $totalActive > 0) {
                    $isStale = true;
                }
            }
        }

        return [
            'status' => $isStale ? 'stale' : 'synced',
            'synced_at' => $cached['synced_at'] ?? null,
            'error' => null,
            'summary' => $cached['summary'] ?? null,
            'total_active_documents' => $totalActive,
            'is_stale' => $isStale,
        ];
    }

    /**
     * Verify whether the property index is ready on FastAPI, and rebuild if needed.
     *
     * @return array{
     *     ready: bool,
     *     rebuilt: bool,
     *     message: string,
     * }
     */
    public function verifyOrRebuildIndex(Property $property): array
    {
        $activeRulesCount = $property->rules()->where('is_active', true)->count();
        $activeFaqsCount = $property->faqs()->where('is_active', true)->count();
        $totalActive = $activeRulesCount + $activeFaqsCount;

        if ($totalActive === 0) {
            return [
                'ready' => true,
                'rebuilt' => false,
                'message' => 'Property has no active documents to index.',
            ];
        }

        $statusCheck = $this->aiClient->getPropertyIndexStatus($property->id);
        if ($statusCheck['success']) {
            $isIndexed = (bool) ($statusCheck['data']['is_indexed'] ?? false);
            $aiCount = (int) ($statusCheck['data']['active_documents_count'] ?? 0);

            if ($isIndexed && $aiCount >= $totalActive) {
                return [
                    'ready' => true,
                    'rebuilt' => false,
                    'message' => 'Index is verified and up-to-date.',
                ];
            }
        }

        // Rebuild from Laravel database
        $syncResult = $this->syncProperty($property);

        return [
            'ready' => $syncResult['success'],
            'rebuilt' => true,
            'message' => $syncResult['message'],
        ];
    }
}
