<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiChatLog;
use App\Models\Property;
use App\Models\User;
use App\Services\Ai\HuniAiClient;
use App\Services\PropertyKnowledgeSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(
        protected HuniAiClient $aiClient,
        protected PropertyKnowledgeSyncService $syncService,
    ) {}

    /**
     * Display the Tenant AI Chat portal interface.
     * Enforces active contract verification and strict property scoping.
     */
    public function index(Request $request): View
    {
        /** @var User $tenant */
        $tenant = Auth::user();

        // 1. Fetch only verified active contracts for this tenant
        $activeContracts = $tenant->contracts()
            ->with('property')
            ->where('status', 'aktif')
            ->get();

        if ($activeContracts->isEmpty()) {
            return view('tenant.chat', [
                'tenant' => $tenant,
                'hasActiveContract' => false,
                'activeContracts' => collect(),
                'selectedProperty' => null,
                'chatLogs' => collect(),
                'indexStatus' => null,
            ]);
        }

        // 2. Select property strictly from verified active contracts
        $selectedPropertyId = $request->integer('property_id');
        $matchingContract = null;

        if ($selectedPropertyId > 0) {
            $matchingContract = $activeContracts->first(
                fn ($c) => (int) $c->property_id === $selectedPropertyId
            );
        }

        if (! $matchingContract) {
            $matchingContract = $activeContracts->first();
        }

        /** @var Property $selectedProperty */
        $selectedProperty = $matchingContract->property;

        // 3. Ensure FastAPI index is populated for this property
        $indexStatus = $this->syncService->verifyOrRebuildIndex($selectedProperty);

        // 4. Load recent conversation history for this tenant & property
        $chatLogs = AiChatLog::query()
            ->where('user_id', $tenant->id)
            ->where('property_id', $selectedProperty->id)
            ->latest('id')
            ->take(20)
            ->get()
            ->reverse()
            ->values();

        return view('tenant.chat', [
            'tenant' => $tenant,
            'hasActiveContract' => true,
            'activeContracts' => $activeContracts,
            'selectedProperty' => $selectedProperty,
            'chatLogs' => $chatLogs,
            'indexStatus' => $indexStatus,
        ]);
    }

    /**
     * Handle incoming question from the tenant.
     * Enforces strict server-side property resolution and authorization.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:500'],
            'property_id' => ['nullable', 'integer'],
        ]);

        /** @var User $tenant */
        $tenant = Auth::user();

        // 1. Verify tenant has an active contract
        $activeContracts = $tenant->contracts()
            ->with('property')
            ->where('status', 'aktif')
            ->get();

        if ($activeContracts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki kontrak aktif pada kos mana pun.',
            ], 403);
        }

        // 2. Determine target property and forbid tampering
        $targetPropertyId = $request->filled('property_id')
            ? (int) $request->input('property_id')
            : (int) $activeContracts->first()->property_id;

        $matchingContract = $activeContracts->first(
            fn ($c) => (int) $c->property_id === $targetPropertyId
        );

        if (! $matchingContract) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak terdaftar pada properti yang diminta.',
            ], 403);
        }

        $startTime = microtime(true);
        $question = trim($validated['question']);

        // 3. Ensure knowledge base index is verified and ready before querying
        /** @var Property $targetProperty */
        $targetProperty = $matchingContract->property;
        $rebuildCheck = $this->syncService->verifyOrRebuildIndex($targetProperty);

        if (! ($rebuildCheck['ready'] ?? false)) {
            $fallbackMessage = 'Layanan asisten AI sedang tidak dapat dihubungi. Silakan hubungi langsung pemilik kos Anda untuk bantuan.';
            $processingTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            try {
                AiChatLog::create([
                    'user_id' => $tenant->id,
                    'property_id' => $targetPropertyId,
                    'session_id' => session()->getId(),
                    'question' => $question,
                    'answer' => $fallbackMessage,
                    'sources' => [],
                    'is_fallback' => true,
                    'relevance_score' => 0.0,
                    'processing_time_ms' => $processingTimeMs,
                    'metadata' => [
                        'error' => 'service_unready',
                        'message' => $rebuildCheck['message'] ?? 'Index not ready',
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('HUNI AI Chat Log creation failed: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'answer' => $fallbackMessage,
                'sources' => [],
                'is_fallback' => true,
                'retrieval_status' => 'service_unreachable',
                'processing_time_ms' => $processingTimeMs,
            ]);
        }

        // 4. Query internal AI FastAPI service
        $result = $this->aiClient->queryKnowledge($targetPropertyId, $question, 3);
        $processingTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        // 5. Handle FastAPI connection or internal service failure gracefully
        if (! ($result['success'] ?? false)) {
            $fallbackMessage = 'Layanan asisten AI sedang tidak dapat dihubungi. Silakan hubungi langsung pemilik kos Anda untuk bantuan.';

            try {
                AiChatLog::create([
                    'user_id' => $tenant->id,
                    'property_id' => $targetPropertyId,
                    'session_id' => session()->getId(),
                    'question' => $question,
                    'answer' => $fallbackMessage,
                    'sources' => [],
                    'is_fallback' => true,
                    'relevance_score' => 0.0,
                    'processing_time_ms' => $processingTimeMs,
                    'metadata' => [
                        'error' => $result['error'] ?? 'service_unavailable',
                        'service_status' => $result['status'] ?? 0,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('HUNI AI Chat Log creation failed: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'answer' => $fallbackMessage,
                'sources' => [],
                'is_fallback' => true,
                'retrieval_status' => 'service_unreachable',
                'processing_time_ms' => $processingTimeMs,
            ]);
        }

        $data = $result['data'] ?? [];
        $answer = $data['answer'] ?? 'Tidak ada jawaban yang dapat diberikan.';
        $sources = $data['sources'] ?? [];
        $isFallback = (bool) ($data['is_fallback'] ?? false);
        $retrievalStatus = (string) ($data['retrieval_status'] ?? 'unknown');

        $topScore = 0.0;
        if (! empty($sources)) {
            $scores = array_column($sources, 'relevance_score');
            $topScore = ! empty($scores) ? (float) max($scores) : 0.0;
        }

        // 6. Store conversation log in ai_chat_logs safely
        $chatLogId = null;
        try {
            $chatLog = AiChatLog::create([
                'user_id' => $tenant->id,
                'property_id' => $targetPropertyId,
                'session_id' => session()->getId(),
                'question' => $question,
                'answer' => $answer,
                'sources' => $sources,
                'is_fallback' => $isFallback,
                'relevance_score' => $topScore,
                'processing_time_ms' => $processingTimeMs,
                'metadata' => [
                    'retrieval_status' => $retrievalStatus,
                    'active_documents_count' => $data['active_documents_count'] ?? 0,
                ],
            ]);
            $chatLogId = $chatLog->id;
        } catch (\Throwable $e) {
            Log::warning('HUNI AI Chat Log save failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'answer' => $answer,
            'sources' => $sources,
            'is_fallback' => $isFallback,
            'retrieval_status' => $retrievalStatus,
            'relevance_score' => $topScore,
            'processing_time_ms' => $processingTimeMs,
            'log_id' => $chatLogId,
        ]);
    }

    /**
     * Clear tenant's conversation history for the selected property.
     */
    public function clearHistory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer'],
        ]);

        /** @var User $tenant */
        $tenant = Auth::user();

        $targetPropertyId = (int) $validated['property_id'];

        // Strict authorization: tenant must have an active contract for this property
        $isAuthorized = $tenant->contracts()
            ->where('status', 'aktif')
            ->where('property_id', $targetPropertyId)
            ->exists();

        if (! $isAuthorized) {
            abort(403, 'Akses ditolak. Anda tidak memiliki kontrak aktif pada properti ini.');
        }

        AiChatLog::query()
            ->where('user_id', $tenant->id)
            ->where('property_id', $targetPropertyId)
            ->delete();

        return back()->with('status', 'Riwayat percakapan berhasil dihapus.');
    }
}
