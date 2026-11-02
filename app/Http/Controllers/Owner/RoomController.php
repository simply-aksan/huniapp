<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreRoomRequest;
use App\Http\Requests\Owner\UpdateRoomRequest;
use App\Models\Facility;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms for the given property.
     */
    public function index(Property $property): View
    {
        Gate::authorize('view', $property);

        $property->load(['rooms.contracts.user', 'rooms.facilities']);

        return view('owner.rooms.index', [
            'property' => $property,
            'rooms' => $property->rooms,
        ]);
    }

    /**
     * Show the form for creating a new room in the property.
     */
    public function create(Property $property): View
    {
        Gate::authorize('update', $property);

        $facilities = Facility::where('category', 'kamar')->orWhere('category', 'umum')->get();

        return view('owner.rooms.create', [
            'property' => $property,
            'facilities' => $facilities,
        ]);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(StoreRoomRequest $request, Property $property): RedirectResponse
    {
        Gate::authorize('update', $property);

        $validated = $request->validated();

        $room = $property->rooms()->create([
            'room_number' => $validated['room_number'],
            'floor' => (int) $validated['floor'],
            'type' => $validated['type'],
            'price' => $validated['price'],
            'size' => $validated['size'] ?? null,
            'capacity' => (int) $validated['capacity'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        if ($request->has('facilities')) {
            $facilityIds = (array) $request->input('facilities');
            $room->facilities()->sync($facilityIds);
        }

        return redirect()->route('owner.properties.rooms.show', [$property, $room])
            ->with('success', "Kamar {$room->room_number} berhasil ditambahkan ke {$property->name}.");
    }

    /**
     * Display the specified room details.
     */
    public function show(Property $property, Room $room): View
    {
        // Enforce that the room belongs to the given property and the owner owns it
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('view', $room);

        $room->load([
            'property',
            'facilities',
            'inventory',
            'contracts' => fn ($q) => $q->with('user')->latest(),
            'complaints' => fn ($q) => $q->with('user')->latest()->take(5),
        ]);

        $activeContract = $room->contracts->firstWhere('status', 'aktif');
        $currentTenant = $activeContract?->user;

        return view('owner.rooms.show', [
            'property' => $property,
            'room' => $room,
            'activeContract' => $activeContract,
            'currentTenant' => $currentTenant,
        ]);
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Property $property, Room $room): View
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('update', $room);

        $facilities = Facility::all();
        $hasActiveContract = $room->contracts()->where('status', 'aktif')->exists();

        return view('owner.rooms.edit', [
            'property' => $property,
            'room' => $room,
            'facilities' => $facilities,
            'hasActiveContract' => $hasActiveContract,
        ]);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(UpdateRoomRequest $request, Property $property, Room $room): RedirectResponse
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('update', $room);

        $validated = $request->validated();

        $room->update([
            'room_number' => $validated['room_number'],
            'floor' => (int) $validated['floor'],
            'type' => $validated['type'],
            'price' => $validated['price'],
            'size' => $validated['size'] ?? null,
            'capacity' => (int) $validated['capacity'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        if ($request->has('facilities')) {
            $facilityIds = (array) $request->input('facilities');
            $room->facilities()->sync($facilityIds);
        }

        return redirect()->route('owner.properties.rooms.show', [$property, $room])
            ->with('success', "Informasi Kamar {$room->room_number} berhasil diperbarui.");
    }

    /**
     * Remove the specified room from storage safely.
     */
    public function destroy(Property $property, Room $room): RedirectResponse
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Kamar tidak ditemukan pada properti ini.');
        }

        Gate::authorize('delete', $room);

        // Security check: cannot delete room with rental contracts
        if ($room->contracts()->exists()) {
            return redirect()->back()->with('error', 'Kamar tidak dapat dihapus karena memiliki riwayat kontrak sewa penghuni.');
        }

        $roomNumber = $room->room_number;
        $room->delete();

        return redirect()->route('owner.properties.show', $property)
            ->with('success', "Kamar {$roomNumber} berhasil dihapus.");
    }
}
