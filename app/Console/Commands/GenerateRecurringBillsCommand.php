<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\InvoiceCreatedNotification;
use App\Services\BillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringBillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:generate-recurring
                            {--period= : Periode penagihan dalam format YYYY-MM (default: bulan ini)}
                            {--contract= : ID kontrak spesifik untuk digenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring monthly rent invoices for eligible active contracts idempotently';

    /**
     * Execute the console command.
     */
    public function handle(BillService $billService): int
    {
        $periodOption = $this->option('period');
        $period = $periodOption ? $billService->normalizePeriod((string) $periodOption) : now()->format('Y-m');

        $query = Contract::where('status', 'aktif')->with(['property', 'room', 'tenant', 'user']);

        if ($this->option('contract')) {
            $query->where('id', (int) $this->option('contract'));
        }

        $contracts = $query->get();
        $totalContracts = $contracts->count();

        $this->info("Memeriksa {$totalContracts} kontrak aktif untuk periode {$period} ({$billService->formatPeriod($period)})...");

        if ($contracts->isEmpty()) {
            $this->warn('Tidak ada kontrak aktif yang ditemukan untuk diproses.');

            return self::SUCCESS;
        }

        $generatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($contracts as $contract) {
            try {
                $bill = $billService->generateRecurringRentInvoice($contract, $period);

                if ($bill && $bill->wasRecentlyCreated) {
                    $generatedCount++;
                    $this->line("  [OK] Kontrak {$contract->contract_number} (Kamar {$contract->room?->room_number}) -> Tagihan: {$bill->bill_number}");

                    // Notify tenant safely without failing invoice creation
                    $tenant = $contract->tenant ?? $contract->user ?? $bill->user;
                    if ($tenant) {
                        try {
                            $tenant->notify(new InvoiceCreatedNotification($bill));
                        } catch (\Throwable $notificationError) {
                            Log::warning("Gagal mengirim notifikasi invoice {$bill->bill_number} ke tenant ID {$tenant->id}: {$notificationError->getMessage()}", [
                                'bill_id' => $bill->id,
                                'contract_id' => $contract->id,
                                'tenant_id' => $tenant->id,
                            ]);
                        }
                    }
                } else {
                    $skippedCount++;
                    $this->line("  [SKIP] Kontrak {$contract->contract_number} (sudah memiliki tagihan atau tidak memenuhi syarat)");
                }
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error("Gagal membuat tagihan sewa berulang untuk kontrak {$contract->contract_number}: {$e->getMessage()}", [
                    'contract_id' => $contract->id,
                    'billing_period' => $period,
                    'exception' => $e,
                ]);
                $this->error("  [FAIL] Kontrak {$contract->contract_number}: {$e->getMessage()}");
            }
        }

        $this->info("Selesai diproses untuk periode {$period}. Total diperiksa: {$totalContracts}, Berhasil dibuat: {$generatedCount}, Dilewati/Sudah ada: {$skippedCount}, Gagal: {$failedCount}.");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
