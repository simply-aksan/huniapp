<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreInventoryRequest;
use App\Http\Requests\Owner\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items owned by the authenticated owner.
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var User $owner */
        $owner = $request->user();

        Gate::authorize('viewAny', Inventory::class);

        $properties = $owner->properties()->with('rooms')->orderBy('name')->get();

        $baseQuery = Inventory::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id));

        // KPI aggregates across all owner inventory
        $totalItems = (int) (clone $baseQuery)->sum('quantity');
        $totalCount = (clone $baseQuery)->count();
        $goodCount = (clone $baseQuery)->whereIn('condition', ['baik', 'bagus'])->count();
        $needsMaintenanceCount = (clone $baseQuery)->whereIn('condition', ['perlu_perawatan', 'rusak_ringan'])->count();
        $damagedCount = (clone $baseQuery)->whereIn('condition', ['rusak', 'rusak_berat', 'hilang'])->count();
        $totalAssetValue = (float) (clone $baseQuery)->sum('purchase_price');

        // Percentages for visual bar indicators
        $goodPercent = $totalCount > 0 ? (int) round(($goodCount / $totalCount) * 100) : 0;
        $needsMaintenancePercent = $totalCount > 0 ? (int) round(($needsMaintenanceCount / $totalCount) * 100) : 0;
        $damagedPercent = $totalCount > 0 ? (int) round(($damagedCount / $totalCount) * 100) : 0;

        // Filtered listing query
        $query = (clone $baseQuery)->with(['property', 'room']);

        // Search filter
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Condition tab filter
        $conditionFilter = $request->input('condition', 'semua');
        if ($conditionFilter === 'baik') {
            $query->whereIn('condition', ['baik', 'bagus']);
        } elseif ($conditionFilter === 'perlu_perawatan') {
            $query->whereIn('condition', ['perlu_perawatan', 'rusak_ringan']);
        } elseif ($conditionFilter === 'rusak') {
            $query->whereIn('condition', ['rusak', 'rusak_berat', 'hilang']);
        }

        // Property filter
        if ($request->filled('property_id')) {
            $query->where('property_id', (int) $request->input('property_id'));
        }

        // Room filter
        if ($request->filled('room_id')) {
            $query->where('room_id', (int) $request->input('room_id'));
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $items = $query->latest('id')->paginate(10)->withQueryString();

        $categories = [
            'Elektronik',
            'Perabot',
            'Sanitasi',
            'Dekorasi',
            'Keamanan',
            'Lainnya',
        ];

        return view('owner.inventory.index', [
            'items' => $items,
            'properties' => $properties,
            'categories' => $categories,
            'totalItems' => $totalItems,
            'totalCount' => $totalCount,
            'goodCount' => $goodCount,
            'goodPercent' => $goodPercent,
            'needsMaintenanceCount' => $needsMaintenanceCount,
            'needsMaintenancePercent' => $needsMaintenancePercent,
            'damagedCount' => $damagedCount,
            'damagedPercent' => $damagedPercent,
            'totalAssetValue' => $totalAssetValue,
            'activeCondition' => $conditionFilter,
            'activePropertyId' => $request->input('property_id'),
            'activeRoomId' => $request->input('room_id'),
            'activeCategory' => $request->input('category'),
            'searchQuery' => $request->input('search', ''),
        ]);
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Inventory::class);

        /** @var User $owner */
        $owner = $request->user();

        $properties = $owner->properties()->with('rooms')->orderBy('name')->get();
        $categories = ['Elektronik', 'Perabot', 'Sanitasi', 'Dekorasi', 'Keamanan', 'Lainnya'];
        $conditions = InventoryCondition::cases();

        $selectedPropertyId = $request->input('property_id');
        $selectedRoomId = $request->input('room_id');

        return view('owner.inventory.create', [
            'properties' => $properties,
            'categories' => $categories,
            'conditions' => $conditions,
            'selectedPropertyId' => $selectedPropertyId,
            'selectedRoomId' => $selectedRoomId,
        ]);
    }

    /**
     * Store a newly created inventory item in storage.
     */
    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Inventory::class);

        $validated = $request->validated();

        $inventory = Inventory::create([
            'property_id' => (int) $validated['property_id'],
            'room_id' => ! empty($validated['room_id']) ? (int) $validated['room_id'] : null,
            'name' => trim((string) $validated['name']),
            'serial_number' => ! empty($validated['serial_number']) ? trim((string) $validated['serial_number']) : null,
            'category' => $validated['category'],
            'brand' => ! empty($validated['brand']) ? trim((string) $validated['brand']) : null,
            'model' => ! empty($validated['model']) ? trim((string) $validated['model']) : null,
            'location' => ! empty($validated['location']) ? trim((string) $validated['location']) : null,
            'quantity' => (int) $validated['quantity'],
            'condition' => $validated['condition'],
            'purchase_date' => $validated['purchase_date'] ?? null,
            'purchase_price' => isset($validated['purchase_price']) ? (float) $validated['purchase_price'] : null,
            'warranty_until' => $validated['warranty_until'] ?? null,
            'last_checked_at' => $validated['last_checked_at'] ?? null,
            'notes' => ! empty($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        return redirect()->route('owner.inventory.index')
            ->with('success', "Barang inventaris '{$inventory->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified inventory item details.
     */
    public function show(Request $request, Inventory $inventory): View|JsonResponse
    {
        Gate::authorize('view', $inventory);

        $inventory->load(['property', 'room']);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $inventory->id,
                'name' => $inventory->name,
                'serial_number' => $inventory->serial_number,
                'category' => $inventory->category,
                'brand' => $inventory->brand,
                'model' => $inventory->model,
                'location' => $inventory->location ?? ($inventory->room ? "Kamar {$inventory->room->room_number}" : $inventory->property?->name),
                'quantity' => $inventory->quantity,
                'condition' => $inventory->condition,
                'condition_label' => $inventory->condition_label,
                'condition_badge' => $inventory->condition_badge_variant,
                'purchase_date' => $inventory->purchase_date?->translatedFormat('d M Y'),
                'purchase_price' => $inventory->purchase_price ? 'Rp '.number_format((float) $inventory->purchase_price, 0, ',', '.') : null,
                'warranty_until' => $inventory->warranty_until?->translatedFormat('d M Y'),
                'last_checked_at' => $inventory->last_checked_at?->translatedFormat('d M Y'),
                'notes' => $inventory->notes,
                'property_name' => $inventory->property?->name,
                'room_number' => $inventory->room?->room_number,
                'edit_url' => route('owner.inventory.edit', $inventory),
                'delete_url' => route('owner.inventory.destroy', $inventory),
            ]);
        }

        return view('owner.inventory.show', [
            'inventory' => $inventory,
        ]);
    }

    /**
     * Show the form for editing the specified inventory item.
     */
    public function edit(Inventory $inventory): View
    {
        Gate::authorize('update', $inventory);

        /** @var User $owner */
        $owner = request()->user();

        $properties = $owner->properties()->with('rooms')->orderBy('name')->get();
        $categories = ['Elektronik', 'Perabot', 'Sanitasi', 'Dekorasi', 'Keamanan', 'Lainnya'];
        $conditions = InventoryCondition::cases();

        return view('owner.inventory.edit', [
            'inventory' => $inventory,
            'properties' => $properties,
            'categories' => $categories,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Update the specified inventory item in storage.
     */
    public function update(UpdateInventoryRequest $request, Inventory $inventory): RedirectResponse
    {
        Gate::authorize('update', $inventory);

        $validated = $request->validated();

        $inventory->update([
            'property_id' => (int) $validated['property_id'],
            'room_id' => ! empty($validated['room_id']) ? (int) $validated['room_id'] : null,
            'name' => trim((string) $validated['name']),
            'serial_number' => ! empty($validated['serial_number']) ? trim((string) $validated['serial_number']) : null,
            'category' => $validated['category'],
            'brand' => ! empty($validated['brand']) ? trim((string) $validated['brand']) : null,
            'model' => ! empty($validated['model']) ? trim((string) $validated['model']) : null,
            'location' => ! empty($validated['location']) ? trim((string) $validated['location']) : null,
            'quantity' => (int) $validated['quantity'],
            'condition' => $validated['condition'],
            'purchase_date' => $validated['purchase_date'] ?? null,
            'purchase_price' => isset($validated['purchase_price']) ? (float) $validated['purchase_price'] : null,
            'warranty_until' => $validated['warranty_until'] ?? null,
            'last_checked_at' => $validated['last_checked_at'] ?? null,
            'notes' => ! empty($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        return redirect()->route('owner.inventory.index')
            ->with('success', "Data inventaris '{$inventory->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified inventory item from storage.
     */
    public function destroy(Inventory $inventory): RedirectResponse
    {
        Gate::authorize('delete', $inventory);

        $name = $inventory->name;
        $inventory->delete();

        return redirect()->route('owner.inventory.index')
            ->with('success', "Barang inventaris '{$name}' berhasil dihapus.");
    }
}
