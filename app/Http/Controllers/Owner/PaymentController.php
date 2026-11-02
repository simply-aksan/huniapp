<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RejectPaymentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Services\BillService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        protected BillService $billService
    ) {}

    /**
     * Display a listing of payments scoped to the authenticated owner's properties.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        /** @var User $owner */
        $owner = $request->user();

        // Scope queries strictly to payments on bills belonging to properties owned by this owner
        $query = Payment::whereHas('bill', function ($bq) use ($owner) {
            $bq->where(function ($q) use ($owner) {
                $q->whereHas('contract.property', fn ($pq) => $pq->where('owner_id', $owner->id))
                    ->orWhereHas('room.property', fn ($pq) => $pq->where('owner_id', $owner->id));
            });
        })->with([
            'bill.contract.property',
            'bill.room.property',
            'user',
            'proofs',
            'verifier',
        ]);

        // 1. Search Query: payment_number, bill_number, tenant name
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('bill', fn ($bq) => $bq->where('bill_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        // 2. Property Filter
        if ($request->filled('property_id')) {
            $propertyId = (int) $request->input('property_id');
            $query->whereHas('bill', function ($bq) use ($propertyId) {
                $bq->where(function ($q) use ($propertyId) {
                    $q->whereHas('contract', fn ($cq) => $cq->where('property_id', $propertyId))
                        ->orWhereHas('room', fn ($rq) => $rq->where('property_id', $propertyId));
                });
            });
        }

        // 3. Status Filter
        if ($request->filled('status') && in_array($request->input('status'), PaymentStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        // 4. Payment Date Filter (single date or date range)
        if ($request->filled('payment_date')) {
            $query->whereDate('payment_date', (string) $request->input('payment_date'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', (string) $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', (string) $request->input('to_date'));
        }

        // Prioritize 'menunggu_verifikasi' first, then sort by newest
        $payments = $query
            ->orderByRaw("CASE WHEN status = '".PaymentStatus::MenungguVerifikasi->value."' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Summary Statistics (scoped to this owner)
        $baseQuery = Payment::whereHas('bill', function ($bq) use ($owner) {
            $bq->where(function ($q) use ($owner) {
                $q->whereHas('contract.property', fn ($pq) => $pq->where('owner_id', $owner->id))
                    ->orWhereHas('room.property', fn ($pq) => $pq->where('owner_id', $owner->id));
            });
        });

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending_count' => (clone $baseQuery)->where('status', PaymentStatus::MenungguVerifikasi->value)->count(),
            'pending_amount' => (float) ((clone $baseQuery)->where('status', PaymentStatus::MenungguVerifikasi->value)->sum('amount')),
            'verified_this_month_count' => (clone $baseQuery)->where('status', PaymentStatus::Berhasil->value)->whereMonth('verified_at', now()->month)->whereYear('verified_at', now()->year)->count(),
            'verified_this_month_amount' => (float) ((clone $baseQuery)->where('status', PaymentStatus::Berhasil->value)->whereMonth('verified_at', now()->month)->whereYear('verified_at', now()->year)->sum('amount')),
            'rejected_count' => (clone $baseQuery)->where('status', PaymentStatus::Ditolak->value)->count(),
        ];

        $properties = $owner->properties()->orderBy('name')->get();

        return view('owner.payments.index', [
            'payments' => $payments,
            'stats' => $stats,
            'properties' => $properties,
            'selectedProperty' => $request->input('property_id', ''),
            'selectedStatus' => $request->input('status', ''),
            'selectedDate' => $request->input('payment_date', ''),
            'searchQuery' => $request->input('q', ''),
        ]);
    }

    /**
     * Display detailed payment verification screen.
     */
    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load([
            'bill.contract.property.owner',
            'bill.contract.tenant',
            'bill.room.property',
            'bill.items',
            'bill.payments.verifier',
            'bill.payments.proofs',
            'user',
            'verifier',
            'proofs',
        ]);

        return view('owner.payments.show', [
            'payment' => $payment,
            'bill' => $payment->bill,
            'canVerify' => Gate::allows('verify', $payment) && $payment->isPending(),
        ]);
    }

    /**
     * Approve the pending payment transaction and reconcile invoice balance.
     */
    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('verify', $payment);

        /** @var User $owner */
        $owner = $request->user();

        // Financial Security: Server ignores any client-supplied amount or status.
        $approvedPayment = $this->billService->approvePayment($payment, $owner);

        return redirect()->route('owner.payments.show', $approvedPayment)
            ->with('success', "Pembayaran {$approvedPayment->payment_number} sebesar Rp ".number_format((float) $approvedPayment->amount, 0, ',', '.').' berhasil disetujui dan saldo tagihan telah diperbarui.');
    }

    /**
     * Reject the pending payment transaction with a recorded reason.
     */
    public function reject(RejectPaymentRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('verify', $payment);

        /** @var User $owner */
        $owner = $request->user();

        $rejectionReason = (string) $request->validated('rejection_reason');
        $rejectedPayment = $this->billService->rejectPayment($payment, $owner, $rejectionReason);

        return redirect()->route('owner.payments.show', $rejectedPayment)
            ->with('success', "Pembayaran {$rejectedPayment->payment_number} telah ditolak dengan alasan yang tercatat.");
    }
}
