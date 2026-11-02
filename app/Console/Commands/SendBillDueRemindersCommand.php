<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Notifications\BillDueReminderNotification;
use App\Notifications\BillOverdueNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendBillDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:send-due-reminders
                            {--date= : Tanggal referensi untuk evaluasi (default: hari ini, format YYYY-MM-DD)}
                            {--force : Abaikan idempotensi (hanya untuk testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat jatuh tempo (H-3) dan keterlambatan (H+1) secara idempotent';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $dateInput = $this->option('date');
        $referenceDate = $dateInput ? Carbon::parse((string) $dateInput)->startOfDay() : now()->startOfDay();

        $this->info("Menjalankan evaluasi pengingat tagihan per tanggal: {$referenceDate->toDateString()}");

        $h3Date = $referenceDate->copy()->addDays(3)->toDateString();
        $h1OverdueDate = $referenceDate->copy()->subDay()->toDateString();

        $activeStatuses = [
            BillStatus::MenungguPembayaran->value,
            BillStatus::Sebagian->value,
        ];

        // 1. Process H-3 Due Reminders
        $upcomingBills = Bill::whereIn('status', $activeStatuses)
            ->whereDate('due_date', $h3Date)
            ->with(['user', 'contract.property', 'room'])
            ->get();

        $sentH3Count = 0;
        $skippedH3Count = 0;

        foreach ($upcomingBills as $bill) {
            if ($bill->outstandingAmount() <= 0) {
                continue;
            }

            // Idempotency: Check if a BillDueReminderNotification has already been sent for this bill
            $alreadySent = ! $this->option('force') && $this->hasNotificationBeenSent(
                BillDueReminderNotification::class,
                (int) $bill->user_id,
                $bill->id
            );

            if ($alreadySent) {
                $skippedH3Count++;

                continue;
            }

            $success = $notificationService->notifyBillDueReminder($bill);
            if ($success) {
                $sentH3Count++;
                $this->line("  [H-3] Pengingat dikirim untuk tagihan {$bill->bill_number} (Jatuh tempo: {$bill->due_date?->format('d/m/Y')})");
            }
        }

        // 2. Process H+1 Overdue Notices
        $overdueBills = Bill::whereIn('status', $activeStatuses)
            ->whereDate('due_date', $h1OverdueDate)
            ->with(['user', 'contract.property', 'room'])
            ->get();

        $sentOverdueCount = 0;
        $skippedOverdueCount = 0;

        foreach ($overdueBills as $bill) {
            if ($bill->outstandingAmount() <= 0) {
                continue;
            }

            // Idempotency: Check if a BillOverdueNotification has already been sent for this bill
            $alreadySent = ! $this->option('force') && $this->hasNotificationBeenSent(
                BillOverdueNotification::class,
                (int) $bill->user_id,
                $bill->id
            );

            if ($alreadySent) {
                $skippedOverdueCount++;

                continue;
            }

            $success = $notificationService->notifyBillOverdue($bill);
            if ($success) {
                $sentOverdueCount++;
                $this->line("  [H+1] Peringatan keterlambatan dikirim untuk tagihan {$bill->bill_number} (Jatuh tempo: {$bill->due_date?->format('d/m/Y')})");
            }
        }

        $this->info('Selesai diproses.');
        $this->info("H-3 Pengingat: {$sentH3Count} terkirim, {$skippedH3Count} dilewati (sudah terkirim/idempotent).");
        $this->info("H+1 Tunggakan: {$sentOverdueCount} terkirim, {$skippedOverdueCount} dilewati (sudah terkirim/idempotent).");

        return self::SUCCESS;
    }

    /**
     * Verify whether a specific notification type has already been sent for a bill.
     */
    protected function hasNotificationBeenSent(string $notificationClass, int $userId, int $billId): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $userId)
            ->where('type', $notificationClass)
            ->where(function ($query) use ($billId) {
                $query->where('data->bill_id', $billId)
                    ->orWhere('data', 'like', '%"bill_id":'.$billId.'%');
            })
            ->exists();
    }
}
