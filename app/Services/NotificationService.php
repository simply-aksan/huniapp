<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BillDueReminderNotification;
use App\Notifications\BillOverdueNotification;
use App\Notifications\ComplaintReplyReceivedNotification;
use App\Notifications\ComplaintStatusUpdatedNotification;
use App\Notifications\ContractActivatedNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\NewComplaintSubmittedNotification;
use App\Notifications\NewPaymentSubmittedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch a notification safely to a user.
     * Prevents any mail transport or queue driver exceptions from aborting or rolling back business transactions.
     */
    public function sendSafely(?User $notifiable, Notification $notification): bool
    {
        if (! $notifiable) {
            Log::info('Notification dispatch skipped: notifiable recipient is null.');

            return false;
        }

        try {
            $notifiable->notify($notification);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi ['.get_class($notification)."] ke user ID {$notifiable->id}: {$e->getMessage()}", [
                'exception' => $e,
                'user_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);

            return false;
        }
    }

    /**
     * Notify tenant when a new invoice is created.
     */
    public function notifyInvoiceCreated(Bill $bill): bool
    {
        $tenant = $bill->user ?? $bill->contract?->tenant;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new InvoiceCreatedNotification($bill));
    }

    /**
     * Notify owner when tenant submits a payment proof awaiting verification.
     */
    public function notifyPaymentSubmitted(Payment $payment): bool
    {
        $bill = $payment->bill;
        $owner = $bill?->contract?->property?->owner
            ?? $bill?->room?->property?->owner
            ?? $payment->bill?->property?->owner;

        if (! $owner) {
            return false;
        }

        return $this->sendSafely($owner, new NewPaymentSubmittedNotification($payment));
    }

    /**
     * Notify tenant when payment is successfully verified and approved by owner.
     */
    public function notifyPaymentVerified(Payment $payment): bool
    {
        $tenant = $payment->user ?? $payment->bill?->user;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new PaymentVerifiedNotification($payment));
    }

    /**
     * Notify tenant when payment is rejected by owner.
     */
    public function notifyPaymentRejected(Payment $payment): bool
    {
        $tenant = $payment->user ?? $payment->bill?->user;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new PaymentRejectedNotification($payment));
    }

    /**
     * Notify tenant when their contract becomes active.
     */
    public function notifyContractActivated(Contract $contract): bool
    {
        $tenant = $contract->tenant ?? $contract->user;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new ContractActivatedNotification($contract));
    }

    /**
     * Notify owner when tenant submits a new complaint ticket.
     */
    public function notifyComplaintCreated(Complaint $complaint): bool
    {
        $owner = $complaint->property?->owner;

        if (! $owner) {
            return false;
        }

        return $this->sendSafely($owner, new NewComplaintSubmittedNotification($complaint));
    }

    /**
     * Notify the counter-party when a new reply is posted to a complaint ticket.
     */
    public function notifyComplaintReplied(Complaint $complaint, ComplaintReply $reply, User $author): bool
    {
        // If author is Tenant, notify Owner
        if ($author->isTenant()) {
            $owner = $complaint->property?->owner;

            return $owner ? $this->sendSafely($owner, new ComplaintReplyReceivedNotification($complaint, $reply, $author)) : false;
        }

        // If author is Owner, notify Tenant who filed the complaint
        $tenant = $complaint->user;

        return $tenant ? $this->sendSafely($tenant, new ComplaintReplyReceivedNotification($complaint, $reply, $author)) : false;
    }

    /**
     * Notify tenant when owner updates the status of a complaint ticket.
     */
    public function notifyComplaintStatusUpdated(Complaint $complaint, string $previousStatus, string $newStatus): bool
    {
        $tenant = $complaint->user;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new ComplaintStatusUpdatedNotification($complaint, $previousStatus, $newStatus));
    }

    /**
     * Notify tenant when invoice due date is approaching (H-3).
     */
    public function notifyBillDueReminder(Bill $bill): bool
    {
        $tenant = $bill->user ?? $bill->contract?->tenant;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new BillDueReminderNotification($bill));
    }

    /**
     * Notify tenant when invoice is overdue (H+1).
     */
    public function notifyBillOverdue(Bill $bill): bool
    {
        $tenant = $bill->user ?? $bill->contract?->tenant;

        if (! $tenant) {
            return false;
        }

        return $this->sendSafely($tenant, new BillOverdueNotification($bill));
    }
}
