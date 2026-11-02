<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StorePropertyRequest;
use App\Http\Requests\Owner\UpdatePropertyRequest;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties owned by the authenticated owner.
     */
    public function index(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        $query = $owner->properties()
            ->withCount([
                'rooms',
                'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'terisi'),
                'rooms as vacant_rooms_count' => fn ($q) => $q->where('status', 'kosong'),
                'contracts as active_tenants_count' => fn ($q) => $q->where('status', 'aktif'),
            ])
            ->with('facilities');

        // Search Filter
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') && in_array($request->input('status'), ['aktif', 'nonaktif'], true)) {
            $query->where('status', $request->input('status'));
        }

        $properties = $query->orderByDesc('is_primary')->latest()->get();

        // Calculate Header Aggregate Stats
        $totalProperties = $owner->properties()->count();
        $totalRooms = Room::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))->count();
        $totalTenants = Contract::where('status', 'aktif')
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->distinct('user_id')
            ->count('user_id');
        $totalRevenue = Bill::whereHas('contract.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->sum('paid_amount');

        return view('owner.properties.index', [
            'properties' => $properties,
            'totalProperties' => $totalProperties,
            'totalRooms' => $totalRooms,
            'totalTenants' => $totalTenants,
            'totalRevenue' => (float) $totalRevenue,
            'activeFilter' => $request->input('status', 'semua'),
            'searchQuery' => $request->input('search', ''),
        ]);
    }

    /**
     * Show the form for creating a new property.
     */
    public function create(): View
    {
        Gate::authorize('create', Property::class);

        return view('owner.properties.create');
    }

    /**
     * Store a newly created property in storage.
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Property::class);

        $validated = $request->validated();
        $owner = $request->user();

        // Generate unique slug if not provided
        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Property::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $isPrimary = $request->boolean('is_primary');

        // If marked primary, unset existing primary for this owner
        if ($isPrimary) {
            $owner->properties()->update(['is_primary' => false]);
        }

        $property = Property::create([
            'owner_id' => $owner->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'district' => $validated['district'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_primary' => $isPrimary,
        ]);

        return redirect()->route('owner.properties.show', $property)
            ->with('success', "Properti '{$property->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified property.
     */
    public function show(Request $request, Property $property): View
    {
        Gate::authorize('view', $property);

        $property->load([
            'rooms.contracts.user',
            'facilities',
            'inventory',
            'complaints' => fn ($q) => $q->with('user', 'room')->latest()->take(5),
        ]);

        $rooms = $property->rooms;
        $totalRooms = $rooms->count();
        $occupiedRooms = $rooms->where('status', 'terisi')->count();
        $vacantRooms = $rooms->where('status', 'kosong')->count();
        $maintenanceRooms = $rooms->where('status', 'perbaikan')->count();
        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;

        return view('owner.properties.show', [
            'property' => $property,
            'rooms' => $rooms,
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'vacantRooms' => $vacantRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupancyRate' => $occupancyRate,
        ]);
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property): View
    {
        Gate::authorize('update', $property);

        return view('owner.properties.edit', [
            'property' => $property,
        ]);
    }

    /**
     * Update the specified property in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();
        $owner = $request->user();

        // Handle slug
        if (! empty($validated['slug'])) {
            $slug = Str::slug($validated['slug']);
        } else {
            $slug = Str::slug($validated['name']);
        }

        // Ensure unique slug excluding current property
        $originalSlug = $slug;
        $counter = 1;
        while (Property::where('slug', $slug)->where('id', '!=', $property->id)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary && ! $property->is_primary) {
            $owner->properties()->where('id', '!=', $property->id)->update(['is_primary' => false]);
        }

        $property->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'district' => $validated['district'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_primary' => $isPrimary,
        ]);

        return redirect()->route('owner.properties.show', $property)
            ->with('success', "Informasi properti '{$property->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified property from storage safely.
     */
    public function destroy(Property $property): RedirectResponse
    {
        Gate::authorize('delete', $property);

        // Security & Integrity check: do not delete if active or historical contracts/bills exist
        $hasContracts = $property->contracts()->exists();
        $hasBills = Bill::whereHas('contract', fn ($q) => $q->where('property_id', $property->id))->exists();

        if ($hasContracts || $hasBills) {
            return redirect()->back()->with('error', 'Properti tidak dapat dihapus karena masih memiliki riwayat kontrak sewa atau transaksi keuangan penghuni.');
        }

        $propertyName = $property->name;
        $property->delete();

        return redirect()->route('owner.properties.index')
            ->with('success', "Properti '{$propertyName}' berhasil dihapus.");
    }
}
