<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\PropertyRuleCategory;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\PropertyRule;
use App\Services\PropertyKnowledgeSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyKnowledgeController extends Controller
{
    public function __construct(
        protected PropertyKnowledgeSyncService $syncService
    ) {}

    /**
     * Display knowledge base management dashboard for a property.
     */
    public function index(Request $request, Property $property): View
    {
        $this->authorizePropertyOwnership($property);

        $rulesQuery = $property->rules()->orderBy('order', 'asc')->orderBy('id', 'asc');
        if ($search = $request->input('rule_search')) {
            $rulesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }
        if ($category = $request->input('rule_category')) {
            $rulesQuery->where('category', $category);
        }
        $rules = $rulesQuery->get();

        $faqsQuery = $property->faqs()->orderBy('order', 'asc')->orderBy('id', 'asc');
        if ($search = $request->input('faq_search')) {
            $faqsQuery->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }
        $faqs = $faqsQuery->get();

        $syncStatus = $this->syncService->getSyncStatus($property);

        return view('owner.properties.knowledge', [
            'property' => $property,
            'rules' => $rules,
            'faqs' => $faqs,
            'categories' => PropertyRuleCategory::cases(),
            'syncStatus' => $syncStatus,
        ]);
    }

    /**
     * Store a newly created property rule.
     */
    public function storeRule(Request $request, Property $property): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', Rule::in(PropertyRuleCategory::values())],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['property_id'] = $property->id;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['order'] = $validated['order'] ?? 0;

        $property->rules()->create($validated);

        return $this->redirectWithSyncResult($property, 'Aturan kos berhasil ditambahkan.');
    }

    /**
     * Update an existing property rule.
     */
    public function updateRule(Request $request, Property $property, PropertyRule $rule): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeRuleBelongsToProperty($property, $rule);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', Rule::in(PropertyRuleCategory::values())],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $rule->is_active);
        $validated['order'] = $validated['order'] ?? $rule->order;

        $rule->update($validated);

        return $this->redirectWithSyncResult($property, 'Aturan kos berhasil diperbarui.');
    }

    /**
     * Toggle active status of a property rule.
     */
    public function toggleRule(Property $property, PropertyRule $rule): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeRuleBelongsToProperty($property, $rule);

        $rule->update(['is_active' => ! $rule->is_active]);

        $statusText = $rule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->redirectWithSyncResult($property, "Aturan kos berhasil {$statusText}.");
    }

    /**
     * Delete a property rule.
     */
    public function destroyRule(Property $property, PropertyRule $rule): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeRuleBelongsToProperty($property, $rule);

        $rule->delete();

        return $this->redirectWithSyncResult($property, 'Aturan kos berhasil dihapus.');
    }

    /**
     * Store a newly created FAQ item.
     */
    public function storeFaq(Request $request, Property $property): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['property_id'] = $property->id;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['order'] = $validated['order'] ?? 0;

        $property->faqs()->create($validated);

        return $this->redirectWithSyncResult($property, 'FAQ resmi berhasil ditambahkan.');
    }

    /**
     * Update an existing FAQ item.
     */
    public function updateFaq(Request $request, Property $property, PropertyFaq $faq): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeFaqBelongsToProperty($property, $faq);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $faq->is_active);
        $validated['order'] = $validated['order'] ?? $faq->order;

        $faq->update($validated);

        return $this->redirectWithSyncResult($property, 'FAQ resmi berhasil diperbarui.');
    }

    /**
     * Toggle active status of a FAQ item.
     */
    public function toggleFaq(Property $property, PropertyFaq $faq): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeFaqBelongsToProperty($property, $faq);

        $faq->update(['is_active' => ! $faq->is_active]);

        $statusText = $faq->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->redirectWithSyncResult($property, "FAQ berhasil {$statusText}.");
    }

    /**
     * Delete a FAQ item.
     */
    public function destroyFaq(Property $property, PropertyFaq $faq): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);
        $this->authorizeFaqBelongsToProperty($property, $faq);

        $faq->delete();

        return $this->redirectWithSyncResult($property, 'FAQ berhasil dihapus.');
    }

    /**
     * Manually trigger full snapshot synchronization to AI service.
     */
    public function sync(Property $property): RedirectResponse
    {
        $this->authorizePropertyOwnership($property);

        $syncResult = $this->syncService->syncProperty($property);

        if ($syncResult['success']) {
            $count = $syncResult['summary']['total_received'] ?? 0;

            return back()->with('success', "Knowledge base berhasil disinkronkan ke AI service ({$count} dokumen aktif terindeks).");
        }

        return back()->with('error', 'Sinkronisasi gagal: '.($syncResult['error'] ?? 'Layanan AI tidak dapat dihubungi.'));
    }

    /**
     * Ensure current user owns the property.
     */
    protected function authorizePropertyOwnership(Property $property): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isOwner() || ! $property->isOwnedBy($user)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola properti ini.');
        }
    }

    /**
     * Ensure rule belongs to the property.
     */
    protected function authorizeRuleBelongsToProperty(Property $property, PropertyRule $rule): void
    {
        if ($rule->property_id !== $property->id) {
            abort(404, 'Aturan tidak ditemukan pada properti ini.');
        }
    }

    /**
     * Ensure FAQ belongs to the property.
     */
    protected function authorizeFaqBelongsToProperty(Property $property, PropertyFaq $faq): void
    {
        if ($faq->property_id !== $property->id) {
            abort(404, 'FAQ tidak ditemukan pada properti ini.');
        }
    }

    /**
     * Attempt automatic sync after database mutations and flash appropriate feedback.
     */
    protected function redirectWithSyncResult(Property $property, string $baseSuccessMessage): RedirectResponse
    {
        $syncResult = $this->syncService->syncProperty($property);

        if ($syncResult['success']) {
            return back()->with('success', "{$baseSuccessMessage} Knowledge base tersinkronkan.");
        }

        return back()
            ->with('success', $baseSuccessMessage)
            ->with('sync_warning', 'Sinkronisasi ke AI service tertunda: '.$syncResult['error']);
    }
}
