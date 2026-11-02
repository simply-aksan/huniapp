<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the Tenant dashboard layout foundation with active contract and bill data.
     */
    public function index(): View
    {
        /** @var User $tenant */
        $tenant = Auth::user();
        $contract = $tenant->contracts()->with(['property', 'room.facilities'])->latest()->first();
        $bills = $tenant->bills()->latest()->take(3)->get();
        $complaints = $tenant->complaints()->with('room')->latest()->take(3)->get();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'contract' => $contract,
            'property' => $contract?->property,
            'room' => $contract?->room,
            'recentBills' => $bills,
            'recentComplaints' => $complaints,
        ]);
    }
}
