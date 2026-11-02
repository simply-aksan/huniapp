<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BillController extends Controller
{
    /**
     * Display a listing of invoices scoped strictly to the authenticated tenant.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Bill::class);

        /** @var User $tenant */
        $tenant = $request->user();

        // Strictly scope query to the authenticated tenant's bills
        $query = Bill::where('user_id', $tenant->id)
            ->with(['contract.property', 'room.property', 'items', 'payments']);

        // 1. Search Query: bill_number or property name
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhereHas('contract.property', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('room.property', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        // 2. Status Filter
        if ($request->filled('status') && in_array($request->input('status'), BillStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        // 3. Type Filter
        if ($request->filled('type') && in_array($request->input('type'), BillType::values(), true)) {
            $query->where('type', $request->input('type'));
        }

        $bills = $query->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Calculate summary KPI cards for this tenant
        $baseQuery = Bill::where('user_id', $tenant->id);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'unpaid_count' => (clone $baseQuery)->whereNotIn('status', [BillStatus::Lunas->value, BillStatus::Dibatalkan->value])->count(),
            'unpaid_amount' => (float) ((clone $baseQuery)->whereNotIn('status', [BillStatus::Lunas->value, BillStatus::Dibatalkan->value])->selectRaw('SUM(total_amount - paid_amount) as unpaid')->value('unpaid') ?? 0),
            'paid_total' => (float) ((clone $baseQuery)->sum('paid_amount')),
        ];

        return view('tenant.bills.index', [
            'bills' => $bills,
            'stats' => $stats,
            'selectedStatus' => $request->input('status', ''),
            'selectedType' => $request->input('type', ''),
            'searchQuery' => $request->input('q', ''),
        ]);
    }

    /**
     * Display the specified invoice detail for the authenticated tenant.
     */
    public function show(Request $request, Bill $bill): View
    {
        // Enforce policy view check
        Gate::authorize('view', $bill);

        // Explicit IDOR protection: bill must belong to the authenticated tenant
        if ($bill->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki hak akses terhadap tagihan ini.');
        }

        $bill->load([
            'contract.property.owner',
            'room.property.owner',
            'items',
            'payments.verifier',
            'payments.proofs',
        ]);

        $outstandingAmount = $bill->outstandingAmount();
        $hasPendingPayment = $bill->hasPendingPayment();
        $canPay = $bill->canAcceptPayment() && $outstandingAmount > 0;

        return view('tenant.bills.show', [
            'bill' => $bill,
            'outstandingAmount' => $outstandingAmount,
            'hasPendingPayment' => $hasPendingPayment,
            'canPay' => $canPay,
        ]);
    }
}
