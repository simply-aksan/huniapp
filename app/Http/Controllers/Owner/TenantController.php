<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants associated with properties owned by the authenticated owner.
     */
    public function index(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        // 1. Determine all tenant user IDs legitimately associated with this owner
        $contractTenantIds = Contract::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->pluck('user_id');

        $invitedTenantIds = Invitation::where('invited_by', $owner->id)
            ->whereNotNull('user_id')
            ->whereIn('status', ['diterima', 'accepted'])
            ->pluck('user_id');

        $tenantIds = $contractTenantIds->concat($invitedTenantIds)->unique()->filter()->values();

        // Base query restricted strictly to these associated tenants
        $query = User::whereIn('id', $tenantIds)
            ->with([
                'contracts' => fn ($q) => $q->whereHas('property', fn ($pq) => $pq->where('owner_id', $owner->id))
                    ->with(['room.property'])
                    ->latest(),
                'acceptedInvitations' => fn ($q) => $q->where('invited_by', $owner->id)
                    ->with('room.property')
                    ->latest(),
            ]);

        // Search Filter (name, email, phone)
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Property Filter
        if ($request->filled('property_id')) {
            $propertyId = (int) $request->input('property_id');
            $query->where(function ($q) use ($propertyId) {
                $q->whereHas('contracts', fn ($cq) => $cq->where('property_id', $propertyId))
                    ->orWhereHas('acceptedInvitations.room', fn ($rq) => $rq->where('property_id', $propertyId));
            });
        }

        // Status Filter (semua, aktif, selesai, baru)
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'aktif') {
            $query->whereHas('contracts', fn ($q) => $q->where('status', 'aktif'));
        } elseif ($statusFilter === 'selesai') {
            $query->whereDoesntHave('contracts', fn ($q) => $q->where('status', 'aktif'))
                ->whereHas('contracts');
        } elseif ($statusFilter === 'baru') {
            $query->whereDoesntHave('contracts')
                ->whereHas('acceptedInvitations');
        }

        $tenants = $query->orderBy('name')->get();

        // Calculate aggregate statistics for header cards
        $totalTenants = $tenantIds->count();
        $activeTenantsCount = Contract::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', 'aktif')
            ->distinct('user_id')
            ->count('user_id');

        $newTenantsCount = Invitation::where('invited_by', $owner->id)
            ->whereNotNull('user_id')
            ->whereIn('status', ['diterima', 'accepted'])
            ->whereDoesntHave('tenant.contracts', fn ($q) => $q->where('status', 'aktif'))
            ->distinct('user_id')
            ->count('user_id');

        $ownerProperties = $owner->properties()->orderBy('name')->get();

        return view('owner.tenants.index', [
            'tenants' => $tenants,
            'totalTenants' => $totalTenants,
            'activeTenantsCount' => $activeTenantsCount,
            'newTenantsCount' => $newTenantsCount,
            'ownerProperties' => $ownerProperties,
            'searchQuery' => $request->input('search', ''),
            'selectedProperty' => $request->input('property_id'),
            'activeStatus' => $statusFilter,
        ]);
    }

    /**
     * Display detailed profile and tenancy history of the specified tenant.
     */
    public function show(Request $request, User $tenant): View
    {
        /** @var User $owner */
        $owner = $request->user();

        // Enforce legitimacy of owner-tenant relationship: 403 Forbidden if not this owner's tenant
        if (! $tenant->isTenantOf($owner)) {
            abort(403, 'Anda tidak memiliki hak akses untuk data penyewa ini.');
        }

        // Load contracts and invitations strictly scoped to this owner's properties
        $contracts = $tenant->contracts()
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->with(['room.property', 'bills'])
            ->latest()
            ->get();

        $activeContract = $contracts->firstWhere('status', 'aktif');

        $invitations = $tenant->acceptedInvitations()
            ->where('invited_by', $owner->id)
            ->with('room.property')
            ->latest()
            ->get();

        $complaints = $tenant->complaints()
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->with('room')
            ->latest()
            ->take(5)
            ->get();

        return view('owner.tenants.show', [
            'tenant' => $tenant,
            'activeContract' => $activeContract,
            'contracts' => $contracts,
            'invitations' => $invitations,
            'complaints' => $complaints,
        ]);
    }
}
