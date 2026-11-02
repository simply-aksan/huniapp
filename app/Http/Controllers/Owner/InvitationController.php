<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations sent by the authenticated owner.
     */
    public function index(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        $query = Invitation::where('invited_by', $owner->id)
            ->with(['room.property', 'tenant']);

        // Search Filter (name, email, room number)
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('room', fn ($rq) => $rq->where('room_number', 'like', "%{$search}%"));
            });
        }

        // Status Filter
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'aktif') {
            $query->whereIn('status', ['aktif', 'pending'])
                ->where('valid_until', '>', now());
        } elseif ($statusFilter === 'diterima') {
            $query->whereIn('status', ['diterima', 'accepted']);
        } elseif ($statusFilter === 'kadaluarsa') {
            $query->where(function ($q) {
                $q->where('status', 'kadaluarsa')
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['aktif', 'pending'])
                            ->where('valid_until', '<=', now());
                    });
            });
        } elseif ($statusFilter === 'dibatalkan') {
            $query->whereIn('status', ['dibatalkan', 'revoked']);
        }

        $invitations = $query->latest()->get();

        // Calculate statistics
        $allOwnerInvitations = Invitation::where('invited_by', $owner->id)->get();
        $totalInvitations = $allOwnerInvitations->count();
        $activeCount = $allOwnerInvitations->filter(fn ($i) => $i->isPending())->count();
        $acceptedCount = $allOwnerInvitations->filter(fn ($i) => $i->isAccepted())->count();
        $expiredCount = $allOwnerInvitations->filter(fn ($i) => $i->isExpired() || $i->isRevoked())->count();

        return view('owner.invitations.index', [
            'invitations' => $invitations,
            'totalInvitations' => $totalInvitations,
            'activeCount' => $activeCount,
            'acceptedCount' => $acceptedCount,
            'expiredCount' => $expiredCount,
            'searchQuery' => $request->input('search', ''),
            'activeStatus' => $statusFilter,
        ]);
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Invitation::class);

        /** @var User $owner */
        $owner = $request->user();

        // Load only owner's properties with available rooms
        $properties = $owner->properties()
            ->with(['rooms' => fn ($q) => $q->where('status', '!=', 'terisi')->orderBy('room_number')])
            ->orderBy('name')
            ->get();

        return view('owner.invitations.create', [
            'properties' => $properties,
            'preselectedPropertyId' => $request->input('property_id'),
            'preselectedRoomId' => $request->input('room_id'),
        ]);
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Invitation::class);

        /** @var User $owner */
        $owner = $request->user();
        $validated = $request->validated();

        // Calculate expiration date (default to 7 days if valid_days given)
        if (! empty($validated['valid_until'])) {
            $validUntil = Carbon::parse($validated['valid_until']);
        } else {
            $days = (int) ($validated['valid_days'] ?? 7);
            $validUntil = now()->addDays($days)->endOfDay();
        }

        // Generate cryptographically secure token
        $token = Str::random(40);

        $invitation = Invitation::create([
            'room_id' => (int) $validated['room_id'],
            'invited_by' => $owner->id,
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'token' => $token,
            'message' => $validated['message'] ?? null,
            'send_via' => 'link',
            'status' => 'aktif',
            'valid_until' => $validUntil,
        ]);

        return redirect()->route('owner.invitations.show', $invitation)
            ->with('success', "Undangan untuk {$invitation->name} berhasil dibuat! Salin link undangan untuk dibagikan kepada calon penyewa.");
    }

    /**
     * Display the specified invitation details and copyable link.
     */
    public function show(Invitation $invitation): View
    {
        Gate::authorize('view', $invitation);

        $invitation->load(['room.property', 'tenant', 'sender']);

        return view('owner.invitations.show', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Revoke / cancel an active invitation.
     */
    public function revoke(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update', $invitation);

        if ($invitation->isAccepted()) {
            return redirect()->back()->with('error', 'Undangan yang sudah diterima tidak dapat dibatalkan.');
        }

        $invitation->update([
            'status' => 'dibatalkan',
        ]);

        return redirect()->back()
            ->with('success', "Undangan untuk {$invitation->name} berhasil dibatalkan.");
    }

    /**
     * Remove the specified invitation from storage.
     */
    public function destroy(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('delete', $invitation);

        if ($invitation->isAccepted()) {
            return redirect()->back()->with('error', 'Undangan yang sudah diterima tidak dapat dihapus demi integritas riwayat penyewa.');
        }

        $name = $invitation->name;
        $invitation->delete();

        return redirect()->route('owner.invitations.index')
            ->with('success', "Undangan untuk {$name} berhasil dihapus.");
    }
}
