<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\FacilityCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AssignFacilityPropertyRequest;
use App\Http\Requests\Owner\AssignFacilityRoomRequest;
use App\Http\Requests\Owner\StoreFacilityRequest;
use App\Http\Requests\Owner\UpdateFacilityRequest;
use App\Models\Facility;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FacilityController extends Controller
{
    /**
     * Display a listing of facilities and properties catalog.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Facility::class);

        /** @var User $owner */
        $owner = $request->user();

        $properties = $owner->properties()
            ->with(['facilities', 'rooms.facilities'])
            ->orderBy('name')
            ->get();

        $query = Facility::withCount([
            'properties' => fn ($q) => $q->where('owner_id', $owner->id),
            'rooms' => fn ($q) => $q->whereHas('property', fn ($p) => $p->where('owner_id', $owner->id)),
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $facilities = $query->orderBy('category')->orderBy('name')->paginate(12)->withQueryString();
        $categories = FacilityCategory::cases();

        return view('owner.facilities.index', [
            'facilities' => $facilities,
            'properties' => $properties,
            'categories' => $categories,
            'selectedCategory' => $request->input('category'),
            'searchQuery' => $request->input('search', ''),
        ]);
    }

    /**
     * Show the form for creating a new facility.
     */
    public function create(): View
    {
        Gate::authorize('create', Facility::class);

        $categories = FacilityCategory::cases();

        return view('owner.facilities.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created facility in storage.
     */
    public function store(StoreFacilityRequest $request): RedirectResponse
    {
        Gate::authorize('create', Facility::class);

        $validated = $request->validated();

        $facility = Facility::create([
            'name' => trim((string) $validated['name']),
            'category' => $validated['category'],
            'icon' => ! empty($validated['icon']) ? trim((string) $validated['icon']) : 'sparkles',
            'description' => ! empty($validated['description']) ? trim((string) $validated['description']) : null,
        ]);

        return redirect()->route('owner.facilities.index')
            ->with('success', "Fasilitas '{$facility->name}' berhasil ditambahkan ke katalog.");
    }

    /**
     * Show the form for editing the specified facility.
     */
    public function edit(Facility $facility): View
    {
        Gate::authorize('update', $facility);

        $categories = FacilityCategory::cases();

        return view('owner.facilities.edit', [
            'facility' => $facility,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified facility in storage.
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): RedirectResponse
    {
        Gate::authorize('update', $facility);

        $validated = $request->validated();

        $facility->update([
            'name' => trim((string) $validated['name']),
            'category' => $validated['category'],
            'icon' => ! empty($validated['icon']) ? trim((string) $validated['icon']) : $facility->icon,
            'description' => ! empty($validated['description']) ? trim((string) $validated['description']) : null,
        ]);

        return redirect()->route('owner.facilities.index')
            ->with('success', "Fasilitas '{$facility->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified facility from storage.
     */
    public function destroy(Request $request, Facility $facility): RedirectResponse
    {
        Gate::authorize('delete', $facility);

        /** @var User $owner */
        $owner = $request->user();

        // Check if facility is currently assigned to this owner's properties or rooms
        $ownPropertiesCount = $facility->properties()->where('owner_id', $owner->id)->count();
        $ownRoomsCount = $facility->rooms()->whereHas('property', fn ($p) => $p->where('owner_id', $owner->id))->count();

        if ($ownPropertiesCount > 0 || $ownRoomsCount > 0) {
            $usageDetails = [];
            if ($ownPropertiesCount > 0) {
                $usageDetails[] = "{$ownPropertiesCount} properti";
            }
            if ($ownRoomsCount > 0) {
                $usageDetails[] = "{$ownRoomsCount} kamar";
            }
            $usageText = implode(' dan ', $usageDetails);

            return redirect()->route('owner.facilities.index')
                ->with('error', "Fasilitas '{$facility->name}' tidak dapat dihapus karena masih digunakan pada {$usageText} Anda. Lepaskan fasilitas dari properti atau kamar terkait terlebih dahulu.");
        }

        $name = $facility->name;

        DB::transaction(function () use ($facility): void {
            // Detach remaining relationships if any and delete
            $facility->properties()->detach();
            $facility->rooms()->detach();
            $facility->delete();
        });

        return redirect()->route('owner.facilities.index')
            ->with('success', "Fasilitas '{$name}' berhasil dihapus dari katalog.");
    }

    /**
     * Assign a facility to a property owned by the user.
     */
    public function assignProperty(AssignFacilityPropertyRequest $request, Property $property): RedirectResponse
    {
        Gate::authorize('update', $property);

        $validated = $request->validated();
        $facilityId = (int) $validated['facility_id'];
        $notes = ! empty($validated['notes']) ? trim((string) $validated['notes']) : null;

        $property->facilities()->syncWithoutDetaching([
            $facilityId => ['notes' => $notes],
        ]);

        return redirect()->back()
            ->with('success', 'Fasilitas properti berhasil diperbarui.');
    }

    /**
     * Detach a facility from a property owned by the user.
     */
    public function detachProperty(Request $request, Property $property, Facility $facility): RedirectResponse
    {
        Gate::authorize('update', $property);

        $property->facilities()->detach($facility->id);

        return redirect()->back()
            ->with('success', "Fasilitas '{$facility->name}' berhasil dilepas dari {$property->name}.");
    }

    /**
     * Assign a facility to a room in a property owned by the user.
     */
    public function assignRoom(AssignFacilityRoomRequest $request, Property $property, Room $room): RedirectResponse
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('update', $room);

        $validated = $request->validated();
        $facilityId = (int) $validated['facility_id'];
        $notes = ! empty($validated['notes']) ? trim((string) $validated['notes']) : null;

        $room->facilities()->syncWithoutDetaching([
            $facilityId => ['notes' => $notes],
        ]);

        return redirect()->back()
            ->with('success', 'Fasilitas kamar berhasil diperbarui.');
    }

    /**
     * Detach a facility from a room.
     */
    public function detachRoom(Request $request, Property $property, Room $room, Facility $facility): RedirectResponse
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('update', $room);

        $room->facilities()->detach($facility->id);

        return redirect()->back()
            ->with('success', "Fasilitas '{$facility->name}' berhasil dilepas dari Kamar {$room->room_number}.");
    }
}
