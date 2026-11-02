<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ComplaintService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseResolvedComplaintsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaints:auto-close-resolved
                            {--days=7 : Jumlah hari ambang batas penyelesaian tiket (default: 7)}
                            {--as-of= : Tanggal patokan penutupan tiket format YYYY-MM-DD (opsional untuk simulasi/testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis menutup tiket pengaduan berstatus selesai yang telah melewati 7 hari (H+7) tanpa aktivitas lanjutan';

    /**
     * Execute the console command.
     */
    public function handle(ComplaintService $complaintService): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('Parameter --days harus berupa angka bulat positif lebih besar dari 0.');

            return self::FAILURE;
        }

        $asOfOption = $this->option('as-of');
        $asOf = null;
        if ($asOfOption) {
            try {
                $asOf = Carbon::parse((string) $asOfOption);
            } catch (\Throwable $e) {
                $this->error("Format tanggal --as-of '{$asOfOption}' tidak valid: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $referenceDate = $asOf ?? now();
        $this->info("Menjalankan auto-close pengaduan berstatus selesai (ambang batas: {$days} hari, patokan: {$referenceDate->toDateString()})...");

        try {
            $closedCount = $complaintService->autoCloseResolvedComplaints($days, $asOf);

            $this->info("Berhasil memproses auto-close. Total tiket yang ditutup: {$closedCount}.");

            Log::info('Command complaints:auto-close-resolved selesai dijalankan.', [
                'closed_count' => $closedCount,
                'threshold_days' => $days,
                'as_of' => $referenceDate->toIso8601String(),
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menjalankan auto-close pengaduan: {$e->getMessage()}");

            Log::error("Command complaints:auto-close-resolved gagal: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return self::FAILURE;
        }
    }
}
