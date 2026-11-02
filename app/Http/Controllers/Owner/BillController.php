<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBillRequest;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\User;
use App\Services\BillService;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BillController extends Controller
{
    public function __construct(
        protected BillService $billService
    ) {}

    /**
     * Display a listing of invoices scoped to the authenticated owner's properties.
     */
    public function index(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        // Scope queries strictly to properties owned by this owner
        $query = Bill::where(function ($q) use ($owner) {
            $q->whereHas('contract.property', fn ($pq) => $pq->where('owner_id', $owner->id))
                ->orWhereHas('room.property', fn ($pq) => $pq->where('owner_id', $owner->id));
        })->with(['contract.property', 'contract.tenant', 'room.property', 'user', 'items', 'payments']);

        // 1. Search Query: bill_number, tenant name, contract_number, room_number
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('contract', fn ($cq) => $cq->where('contract_number', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($rq) => $rq->where('room_number', 'like', "%{$search}%"));
            });
        }

        // 2. Property Filter
        if ($request->filled('property_id')) {
            $propertyId = (int) $request->input('property_id');
            $query->where(function ($q) use ($propertyId) {
                $q->whereHas('contract', fn ($cq) => $cq->where('property_id', $propertyId))
                    ->orWhereHas('room', fn ($rq) => $rq->where('property_id', $propertyId));
            });
        }

        // 3. Billing Period Filter
        if ($request->filled('billing_period')) {
            $period = trim((string) $request->input('billing_period'));
            $query->where('billing_period', $period);
        }

        // 4. Type Filter
        if ($request->filled('type') && in_array($request->input('type'), BillType::values(), true)) {
            $query->where('type', $request->input('type'));
        }

        // 5. Status Filter
        if ($request->filled('status') && in_array($request->input('status'), BillStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        // 6. Sorting Whitelist
        $allowedSorts = ['issue_date', 'due_date', 'total_amount', 'bill_number', 'created_at'];
        $sortBy = (string) $request->input('sort', 'created_at');
        $sortDir = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $bills = $query->orderBy($sortBy, $sortDir)->paginate(10)->withQueryString();

        // Summary Statistics (scoped to this owner)
        $baseQuery = Bill::where(function ($q) use ($owner) {
            $q->whereHas('contract.property', fn ($pq) => $pq->where('owner_id', $owner->id))
                ->orWhereHas('room.property', fn ($pq) => $pq->where('owner_id', $owner->id));
        });

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'unpaid_count' => (clone $baseQuery)->whereNotIn('status', [BillStatus::Lunas->value, BillStatus::Dibatalkan->value])->count(),
            'unpaid_amount' => (float) ((clone $baseQuery)->whereNotIn('status', [BillStatus::Lunas->value, BillStatus::Dibatalkan->value])->selectRaw('SUM(total_amount - paid_amount) as unpaid')->value('unpaid') ?? 0),
            'paid_this_month' => (float) ((clone $baseQuery)->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year)->sum('paid_amount')),
        ];

        $properties = $owner->properties()->orderBy('name')->get();

        return view('owner.bills.index', [
            'bills' => $bills,
            'stats' => $stats,
            'properties' => $properties,
            'selectedProperty' => $request->input('property_id', ''),
            'selectedPeriod' => $request->input('billing_period', ''),
            'selectedType' => $request->input('type', ''),
            'selectedStatus' => $request->input('status', ''),
            'searchQuery' => $request->input('q', ''),
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    /**
     * Show the form for creating a new invoice manually.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Bill::class);

        /** @var User $owner */
        $owner = $request->user();

        // Fetch eligible contracts (both active and historical) owned by this owner
        $contracts = Contract::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->with(['property', 'room', 'tenant'])
            ->orderByDesc('id')
            ->get();

        $selectedContractId = (int) $request->input('contract_id', 0);

        return view('owner.bills.create', [
            'contracts' => $contracts,
            'selectedContractId' => $selectedContractId,
            'defaultIssueDate' => now()->toDateString(),
            'defaultDueDate' => now()->addDays(7)->toDateString(),
            'currentPeriod' => now()->format('Y-m'),
        ]);
    }

    /**
     * Store a newly created invoice and its line items atomically.
     */
    public function store(StoreBillRequest $request): RedirectResponse
    {
        Gate::authorize('create', Bill::class);

        $validated = $request->validated();

        /** @var Contract $contract */
        $contract = Contract::with(['property', 'room'])->findOrFail((int) $validated['contract_id']);

        $type = (string) $validated['type'];
        $period = isset($validated['billing_period']) && ! empty($validated['billing_period'])
            ? (string) $validated['billing_period']
            : now()->format('Y-m');

        $issueDate = (string) $validated['issue_date'];
        $dueDate = ! empty($validated['due_date'])
            ? (string) $validated['due_date']
            : $this->billService->calculateDueDate($issueDate)->toDateString();

        $dedupKey = null;
        if ($type === BillType::Sewa->value) {
            $dedupKey = "rent:{$contract->id}:{$period}";
        } elseif ($type === BillType::Deposit->value) {
            $dedupKey = "deposit:{$contract->id}";
        }

        $bill = $this->billService->createInvoice([
            'contract_id' => $contract->id,
            'user_id' => $contract->user_id,
            'room_id' => $contract->room_id,
            'type' => $type,
            'deduplication_key' => $dedupKey,
            'billing_period' => $period,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'notes' => $validated['notes'] ?? null,
        ], (array) $validated['items']);

        // Safely notify tenant of newly created invoice
        app(NotificationService::class)->notifyInvoiceCreated($bill);

        return redirect()->route('owner.bills.show', $bill)
            ->with('success', "Tagihan {$bill->bill_number} berhasil dibuat.");
    }

    /**
     * Display the specified invoice detail.
     */
    public function show(Bill $bill): View
    {
        Gate::authorize('view', $bill);

        $bill->load([
            'contract.property.owner',
            'contract.tenant',
            'room.property',
            'user',
            'items',
            'payments.verifier',
            'payments.proofs',
        ]);

        return view('owner.bills.show', [
            'bill' => $bill,
            'canCancel' => Gate::allows('cancel', $bill),
        ]);
    }

    /**
     * Cancel the specified invoice explicitly with reason logging.
     */
    public function cancel(Request $request, Bill $bill): RedirectResponse
    {
        Gate::authorize('cancel', $bill);

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan tagihan wajib diisi.',
            'cancellation_reason.min' => 'Alasan pembatalan tagihan minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($bill, $request) {
            /** @var Bill $lockedBill */
            $lockedBill = Bill::where('id', $bill->id)->lockForUpdate()->firstOrFail();

            if ($lockedBill->isPaid()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Tagihan yang sudah lunas tidak dapat dibatalkan.',
                ]);
            }

            if ($lockedBill->isCancelled()) {
                return;
            }

            if ($lockedBill->payments()->where('status', 'berhasil')->exists()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Tagihan tidak dapat dibatalkan karena memiliki riwayat pembayaran yang telah diverifikasi.',
                ]);
            }

            if ($lockedBill->hasPendingPayment()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Tagihan memiliki pembayaran yang sedang menunggu verifikasi. Selesaikan verifikasi pembayaran terlebih dahulu.',
                ]);
            }

            $reason = trim((string) $request->input('cancellation_reason'));
            $noteSuffix = "\n[Dibatalkan pada ".now()->format('d/m/Y H:i')." oleh Pemilik: {$reason}]";

            $lockedBill->update([
                'status' => BillStatus::Dibatalkan->value,
                'notes' => trim(($lockedBill->notes ?? '').$noteSuffix),
            ]);
        });

        return redirect()->route('owner.bills.show', $bill)
            ->with('success', "Tagihan {$bill->bill_number} telah dibatalkan.");
    }
}
