<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Show the Owner dashboard layout foundation with real property summary.
     */
    public function index(): View
    {
        /** @var User $owner */
        $owner = Auth::user();

        // 1. Properties
        $properties = $owner->properties()
            ->withCount([
                'rooms',
                'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'terisi'),
            ])
            ->get();
        $totalProperties = $properties->count();
        $primaryProperty = $properties->firstWhere('is_primary', true) ?? $properties->first();

        // 2. Rooms aggregate across all owner's properties
        $roomCounts = Room::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'terisi' then 1 end) as occupied")
            ->selectRaw("count(case when status = 'kosong' then 1 end) as vacant")
            ->selectRaw("count(case when status = 'perbaikan' then 1 end) as maintenance")
            ->first();

        $totalRooms = (int) ($roomCounts?->total ?? 0);
        $occupiedRooms = (int) ($roomCounts?->occupied ?? 0);
        $vacantRooms = (int) ($roomCounts?->vacant ?? 0);
        $maintenanceRooms = (int) ($roomCounts?->maintenance ?? 0);
        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;

        // 3. Active Tenants
        $totalTenants = Contract::where('status', 'aktif')
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->distinct('user_id')
            ->count('user_id');

        // 4. Financial / Bills Summary
        $thisMonth = now();
        $thisMonthRevenue = $this->analyticsService->getOwnerVerifiedRevenue(
            $owner,
            $thisMonth->copy()->startOfMonth(),
            $thisMonth->copy()->endOfMonth()
        );

        $lastMonth = now()->subMonth();
        $lastMonthRevenue = $this->analyticsService->getOwnerVerifiedRevenue(
            $owner,
            $lastMonth->copy()->startOfMonth(),
            $lastMonth->copy()->endOfMonth()
        );

        $revenueGrowth = null;
        if ($lastMonthRevenue > 0) {
            $revenueGrowth = round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
        }

        $pendingBillsCount = Bill::whereHas('contract.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', '!=', 'lunas')
            ->count();

        $pendingBillsAmount = (float) (Bill::whereHas('contract.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', '!=', 'lunas')
            ->selectRaw('SUM(total_amount - paid_amount) as total_unpaid')
            ->value('total_unpaid') ?? 0);

        // 5. Recent Bills (latest 5)
        $recentBills = Bill::with(['contract.user', 'room', 'contract.property'])
            ->whereHas('contract.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->latest('issue_date')
            ->take(5)
            ->get();

        // 6. Recent Complaints (latest 5)
        $recentComplaints = Complaint::with(['room', 'user', 'property'])
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->latest()
            ->take(5)
            ->get();

        // 7. Monthly Revenue Breakdown (past 6 months using verified payments)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $endOfThisMonth = now()->endOfMonth();

        $sixMonthsPayments = Payment::where('status', PaymentStatus::Berhasil->value)
            ->whereHas('bill', function ($bq) use ($owner) {
                $bq->where(function ($sq) use ($owner) {
                    $sq->whereHas('contract.property', fn ($pq) => $pq->where('owner_id', $owner->id))
                        ->orWhereHas('room.property', fn ($pq) => $pq->where('owner_id', $owner->id));
                });
            })
            ->where(function ($dq) use ($sixMonthsAgo, $endOfThisMonth) {
                $dq->where(function ($q1) use ($sixMonthsAgo, $endOfThisMonth) {
                    $q1->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$sixMonthsAgo, $endOfThisMonth]);
                })->orWhere(function ($q2) use ($sixMonthsAgo, $endOfThisMonth) {
                    $q2->whereNull('verified_at')
                        ->whereBetween('payment_date', [$sixMonthsAgo, $endOfThisMonth]);
                });
            })
            ->get();

        $monthlySums = [];
        foreach ($sixMonthsPayments as $p) {
            $recDate = $p->verified_at ?? $p->payment_date;
            if ($recDate) {
                $k = $recDate->format('Y-m');
                $monthlySums[$k] = ($monthlySums[$k] ?? 0.0) + (float) $p->amount;
            }
        }

        $monthlyChartData = [];
        $maxChartAmount = 1;
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $key = $monthDate->format('Y-m');
            $label = $monthDate->translatedFormat('M');
            $amount = (float) ($monthlySums[$key] ?? 0);
            if ($amount > $maxChartAmount) {
                $maxChartAmount = $amount;
            }
            $monthlyChartData[] = [
                'month' => $label,
                'full_month' => $monthDate->translatedFormat('F Y'),
                'amount' => $amount,
                'is_current' => $i === 0,
            ];
        }

        // 8. Recent Activities (combining verified payments & new complaints)
        $recentPayments = Payment::with(['bill.user', 'bill.room'])
            ->whereHas('bill.contract.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->latest('payment_date')
            ->take(4)
            ->get()
            ->map(fn ($p) => [
                'type' => 'payment',
                'title' => $p->status === 'berhasil' ? 'Pembayaran diterima' : 'Pembayaran tercatat',
                'subtitle' => ($p->bill?->user?->name ?? 'Penghuni').' — Kamar '.($p->bill?->room?->room_number ?? '-'),
                'amount' => $p->amount,
                'badge' => $p->status === 'berhasil' ? 'sukses' : 'pending',
                'time' => Carbon::parse($p->payment_date),
            ]);

        $recentComplaintActivities = $recentComplaints->take(4)->map(fn ($c) => [
            'type' => 'complaint',
            'title' => 'Keluhan baru: '.$c->title,
            'subtitle' => 'Kamar '.($c->room?->room_number ?? '-').' • '.$c->category,
            'amount' => null,
            'badge' => $c->priority === 'tinggi' ? 'bahaya' : 'warning',
            'time' => $c->created_at,
        ]);

        $recentActivities = $recentPayments->concat($recentComplaintActivities)
            ->sortByDesc('time')
            ->take(5)
            ->values();

        return view('owner.dashboard', [
            'owner' => $owner,
            'properties' => $properties,
            'primaryProperty' => $primaryProperty,
            'totalProperties' => $totalProperties,
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'vacantRooms' => $vacantRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupancyRate' => $occupancyRate,
            'totalTenants' => $totalTenants,
            'thisMonthRevenue' => $thisMonthRevenue,
            'revenueGrowth' => $revenueGrowth,
            'pendingBillsCount' => $pendingBillsCount,
            'pendingBillsAmount' => $pendingBillsAmount,
            'recentBills' => $recentBills,
            'recentComplaints' => $recentComplaints,
            'monthlyChartData' => $monthlyChartData,
            'maxChartAmount' => $maxChartAmount,
            'recentActivities' => $recentActivities,
        ]);
    }
}
