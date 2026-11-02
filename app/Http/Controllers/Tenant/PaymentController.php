<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePaymentRequest;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Services\BillService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected BillService $billService
    ) {}

    /**
     * Submit a new payment and upload payment proof atomically with concurrency protection.
     */
    public function store(StorePaymentRequest $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        /** @var array<int, array{path: string, name: string, size: int, mime: string}> $savedFiles */
        $savedFiles = [];

        try {
            // 1. Process and upload files to private storage
            if ($request->hasFile('proofs')) {
                foreach ((array) $request->file('proofs') as $file) {
                    if ($file) {
                        $storedPath = Storage::disk('local')->putFile('payments', $file);
                        $savedFiles[] = [
                            'path' => $storedPath,
                            'name' => $file->getClientOriginalName(),
                            'size' => (int) $file->getSize(),
                            'mime' => (string) $file->getMimeType(),
                        ];
                    }
                }
            }

            // 2. Perform atomic database transaction with row-level locking
            $payment = DB::transaction(function () use ($bill, $validated, $user, $savedFiles) {
                /** @var Bill $lockedBill */
                $lockedBill = Bill::where('id', $bill->id)->lockForUpdate()->firstOrFail();

                // Enforce tenant authorization under lock
                if ($lockedBill->user_id !== $user->id) {
                    abort(403, 'Akses ditolak.');
                }

                // Check eligibility under lock
                if ($lockedBill->isPaid() || $lockedBill->isCancelled()) {
                    throw ValidationException::withMessages([
                        'amount' => 'Tagihan ini tidak dapat menerima pembayaran baru karena sudah lunas atau dibatalkan.',
                    ]);
                }

                // Check pending payment guard under lock
                if ($lockedBill->hasPendingPayment()) {
                    throw ValidationException::withMessages([
                        'amount' => 'Tagihan ini memiliki pembayaran yang sedang menunggu verifikasi pemilik kos.',
                    ]);
                }

                // Verify outstanding balance under lock
                $amount = (float) $validated['amount'];
                $outstanding = $lockedBill->outstandingAmount();

                if ($amount > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal pembayaran melebihi sisa tagihan (Maksimal: Rp '.number_format($outstanding, 0, ',', '.').').',
                    ]);
                }

                // Allocate globally unique payment transaction number
                $paymentNumber = $this->billService->generatePaymentNumber();

                // Persist Payment record strictly as 'menunggu_verifikasi'
                // Reject client spoofing: verifier and verified_at remain null
                $newPayment = Payment::create([
                    'payment_number' => $paymentNumber,
                    'bill_id' => $lockedBill->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'payment_date' => $validated['payment_date'],
                    'status' => PaymentStatus::MenungguVerifikasi->value,
                    'verified_by' => null,
                    'verified_at' => null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Persist PaymentProof records
                foreach ($savedFiles as $fileData) {
                    PaymentProof::create([
                        'payment_id' => $newPayment->id,
                        'file_name' => $fileData['name'],
                        'file_path' => $fileData['path'],
                        'file_size' => $fileData['size'],
                        'mime_type' => $fileData['mime'],
                    ]);
                }

                return $newPayment;
            });

            // Safely notify property owner that payment requires verification
            app(NotificationService::class)->notifyPaymentSubmitted($payment);

            return redirect()->route('tenant.bills.show', $bill)
                ->with('success', 'Bukti pembayaran '.number_format((float) $payment->amount, 0, ',', '.').' berhasil dikirim dan menunggu verifikasi.');
        } catch (\Throwable $e) {
            // Clean up newly uploaded files on failure
            foreach ($savedFiles as $fileData) {
                try {
                    if (Storage::disk('local')->exists($fileData['path'])) {
                        Storage::disk('local')->delete($fileData['path']);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::error("Gagal menghapus file bukti pembayaran sementara '{$fileData['path']}': ".$cleanupException->getMessage());
                }
            }

            throw $e;
        }
    }

    /**
     * Download or stream payment proof file with strict authorization.
     */
    public function downloadProof(Request $request, Payment $payment, PaymentProof $proof): StreamedResponse
    {
        // Integrity check: proof must belong to payment
        abort_if($proof->payment_id !== $payment->id, 404, 'Bukti pembayaran tidak terkait dengan transaksi ini.');

        // Enforce authorization policy (view / downloadProof)
        Gate::authorize('downloadProof', $payment);

        $disk = Storage::disk('local');
        if (! $disk->exists($proof->file_path)) {
            abort(404, 'File bukti pembayaran tidak ditemukan di penyimpanan server.');
        }

        return $disk->download($proof->file_path, $proof->file_name);
    }
}
