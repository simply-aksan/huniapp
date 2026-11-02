<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Get complete report dataset for an owner based on filter criteria.
     *
     * @param  array{
     *     report_type?: string,
     *     property_id?: int|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     period_type?: string|null,
     *     period?: string|null,
     *     status?: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function getReportData(User $owner, array $filters = []): array
    {
        $reportType = $this->normalizeReportType($filters['report_type'] ?? 'revenue');
        $propertyId = ! empty($filters['property_id']) ? (int) $filters['property_id'] : null;
        $statusFilter = ! empty($filters['status']) ? (string) $filters['status'] : null;

        $property = null;
        if ($propertyId) {
            $property = $owner->properties()->find($propertyId);
        }

        [$startDate, $endDate, $periodLabel] = $this->resolveReportDates($filters);

        $report = match ($reportType) {
            'revenue' => $this->buildRevenueReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            'payments' => $this->buildPaymentsReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            'bills' => $this->buildBillsReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            'occupancy' => $this->buildOccupancyReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            'complaints' => $this->buildComplaintsReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            'inventory' => $this->buildInventoryReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
            default => $this->buildRevenueReport($owner, $propertyId, $startDate, $endDate, $statusFilter),
        };

        return array_merge([
            'reportType' => $reportType,
            'owner' => $owner,
            'property' => $property,
            'propertyName' => $property?->name ?? 'Semua Properti',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $periodLabel,
            'generatedAt' => now(),
            'filters' => $filters,
        ], $report);
    }

    /**
     * Stream native CSV download for owner report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(User $owner, array $filters = []): StreamedResponse
    {
        $data = $this->getReportData($owner, $filters);
        $filename = $this->generateCsvFilename($data['reportType'], $data['startDate'], $data['endDate']);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write UTF-8 Byte Order Mark (BOM) for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header metadata section
            fputcsv($handle, ['HUNI — LAPORAN '.strtoupper((string) $data['reportTitle'])]);
            fputcsv($handle, ['Pemilik', $this->sanitizeCsvCell($data['owner']->name)]);
            fputcsv($handle, ['Properti', $this->sanitizeCsvCell($data['propertyName'])]);
            fputcsv($handle, ['Periode', $this->sanitizeCsvCell($data['periodLabel'])]);
            fputcsv($handle, ['Tanggal Cetak', $this->sanitizeCsvCell($data['generatedAt']->format('d/m/Y H:i:s'))]);
            fputcsv($handle, []);

            // Summary metrics section
            fputcsv($handle, ['RINGKASAN']);
            foreach ($data['summary'] as $metric) {
                fputcsv($handle, [
                    $this->sanitizeCsvCell($metric['label']),
                    $this->sanitizeCsvCell($metric['value']),
                ]);
            }
            fputcsv($handle, []);

            // Data table header
            $headers = array_map(fn ($h) => $this->sanitizeCsvCell($h), $data['tableHeaders']);
            fputcsv($handle, $headers);

            // Data rows
            foreach ($data['tableRows'] as $row) {
                $cells = array_map(fn ($v) => $this->sanitizeCsvCell($v), $row['csvCells']);
                fputcsv($handle, $cells);
            }

            // Total / Footer row if exists
            if (! empty($data['totalRowCsv'])) {
                $footerCells = array_map(fn ($v) => $this->sanitizeCsvCell($v), $data['totalRowCsv']);
                fputcsv($handle, $footerCells);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Sanitize cell value to prevent CSV Formula Injection.
     * Escapes characters (=, +, -, @, \t, \r) at the start of text cells with a single quote ('),
     * while preserving legitimate numeric values intact.
     */
    public function sanitizeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        // Preserve genuine numbers (including currency amounts and negatives)
        if (is_numeric($value)) {
            return (string) $value;
        }

        $str = (string) $value;
        $trimmed = ltrim($str);

        // Mitigate formula injection if string starts with formula trigger characters
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * A. Laporan Pendapatan (Verified Cash Revenue).
     *
     * @return array<string, mixed>
     */
    protected function buildRevenueReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $payments = $this->baseVerifiedPaymentQuery($owner, $propertyId)
            ->where(function ($dq) use ($startDate, $endDate) {
                $dq->where(function ($q1) use ($startDate, $endDate) {
                    $q1->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$startDate, $endDate]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('verified_at')
                        ->whereBetween('payment_date', [$startDate, $endDate]);
                });
            })
            ->with(['bill.contract.property', 'bill.room.property', 'user', 'verifier'])
            ->get()
            ->sortByDesc(fn (Payment $p) => ($p->verified_at ?? $p->payment_date)?->timestamp);

        $totalRevenue = 0.0;
        $tableRows = [];

        $index = 1;
        foreach ($payments as $payment) {
            $bill = $payment->bill;
            $prop = $payment->property();
            $roomNumber = $bill?->room?->room_number ?? $bill?->contract?->room?->room_number ?? '-';
            $billTypeLabel = $bill?->typeEnum()?->label() ?? 'Sewa';
            $recDate = $payment->verified_at ?? $payment->payment_date;
            $amount = (float) $payment->amount;
            $totalRevenue += $amount;

            $tableRows[] = [
                'index' => $index++,
                'payment_number' => $payment->payment_number,
                'bill_number' => $bill?->bill_number ?? '-',
                'tenant_name' => $payment->user?->name ?? 'Penghuni',
                'property_name' => $prop?->name ?? '-',
                'room_number' => $roomNumber,
                'bill_type' => $billTypeLabel,
                'recognition_date' => $recDate?->format('d/m/Y H:i') ?? '-',
                'amount' => $amount,
                'csvCells' => [
                    $payment->payment_number,
                    $bill?->bill_number ?? '-',
                    $payment->user?->name ?? 'Penghuni',
                    $prop?->name ?? '-',
                    $roomNumber,
                    $billTypeLabel,
                    $recDate?->format('d/m/Y H:i') ?? '-',
                    $amount,
                ],
            ];
        }

        $totalTransactions = count($tableRows);
        $avgPerTransaction = $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 2) : 0.0;

        return [
            'reportTitle' => 'Laporan Pendapatan Kas Terverifikasi',
            'summary' => [
                ['label' => 'Total Pendapatan Terverifikasi', 'value' => 'Rp '.number_format($totalRevenue, 0, ',', '.'), 'raw' => $totalRevenue],
                ['label' => 'Total Transaksi', 'value' => (string) $totalTransactions, 'raw' => $totalTransactions],
                ['label' => 'Rata-rata per Transaksi', 'value' => 'Rp '.number_format($avgPerTransaction, 0, ',', '.'), 'raw' => $avgPerTransaction],
            ],
            'tableHeaders' => [
                'No. Pembayaran',
                'No. Invoice',
                'Penghuni',
                'Properti',
                'Kamar',
                'Jenis Tagihan',
                'Tgl Pengakuan',
                'Nominal (Rp)',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                '',
                '',
                '',
                '',
                '',
                $totalTransactions.' Transaksi',
                $totalRevenue,
            ],
            'totalRowDisplay' => [
                'total_amount' => $totalRevenue,
                'total_count' => $totalTransactions,
            ],
        ];
    }

    /**
     * B. Laporan Pembayaran (All Payment Statuses).
     *
     * @return array<string, mixed>
     */
    protected function buildPaymentsReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $query = Payment::whereHas('bill', function ($bq) use ($owner, $propertyId) {
            $bq->where(function ($sq) use ($owner, $propertyId) {
                $sq->whereHas('contract.property', function ($pq) use ($owner, $propertyId) {
                    $pq->where('owner_id', $owner->id);
                    if ($propertyId) {
                        $pq->where('id', $propertyId);
                    }
                })->orWhereHas('room.property', function ($pq) use ($owner, $propertyId) {
                    $pq->where('owner_id', $owner->id);
                    if ($propertyId) {
                        $pq->where('id', $propertyId);
                    }
                });
            });
        })->whereBetween('payment_date', [$startDate, $endDate]);

        if ($statusFilter && in_array($statusFilter, [
            PaymentStatus::MenungguVerifikasi->value,
            PaymentStatus::Berhasil->value,
            PaymentStatus::Ditolak->value,
        ], true)) {
            $query->where('status', $statusFilter);
        }

        $payments = $query->with(['bill.contract.property', 'bill.room.property', 'user', 'verifier'])
            ->orderByDesc('payment_date')
            ->get();

        $totalSuccessful = 0.0;
        $countSuccessful = 0;
        $totalPending = 0.0;
        $countPending = 0;
        $totalRejected = 0.0;
        $countRejected = 0;

        $tableRows = [];
        $index = 1;

        foreach ($payments as $p) {
            $bill = $p->bill;
            $prop = $p->property();
            $roomNumber = $bill?->room?->room_number ?? $bill?->contract?->room?->room_number ?? '-';
            $amount = (float) $p->amount;
            $statusEnum = $p->statusEnum();
            $statusLabel = $statusEnum?->label() ?? ucfirst((string) $p->status);

            if ($p->isSuccessful()) {
                $totalSuccessful += $amount;
                $countSuccessful++;
            } elseif ($p->isPending()) {
                $totalPending += $amount;
                $countPending++;
            } elseif ($p->isRejected()) {
                $totalRejected += $amount;
                $countRejected++;
            }

            $tableRows[] = [
                'index' => $index++,
                'payment_number' => $p->payment_number,
                'bill_number' => $bill?->bill_number ?? '-',
                'tenant_name' => $p->user?->name ?? 'Penghuni',
                'property_name' => $prop?->name ?? '-',
                'room_number' => $roomNumber,
                'payment_date' => $p->payment_date?->format('d/m/Y H:i') ?? '-',
                'verified_at' => $p->verified_at?->format('d/m/Y H:i') ?? '-',
                'method' => $p->payment_method ?? 'Transfer Bank',
                'amount' => $amount,
                'status' => $statusLabel,
                'status_raw' => $p->status,
                'badge_variant' => $p->statusBadgeVariant(),
                'verifier' => $p->verifier?->name ?? '-',
                'csvCells' => [
                    $p->payment_number,
                    $bill?->bill_number ?? '-',
                    $p->user?->name ?? 'Penghuni',
                    $prop?->name ?? '-',
                    $roomNumber,
                    $p->payment_date?->format('d/m/Y H:i') ?? '-',
                    $p->verified_at?->format('d/m/Y H:i') ?? '-',
                    $p->payment_method ?? 'Transfer Bank',
                    $amount,
                    $statusLabel,
                    $p->verifier?->name ?? '-',
                ],
            ];
        }

        return [
            'reportTitle' => 'Laporan Transaksi Pembayaran',
            'summary' => [
                ['label' => 'Total Pembayaran Berhasil', 'value' => 'Rp '.number_format($totalSuccessful, 0, ',', '.')." ({$countSuccessful} transaksi)", 'raw' => $totalSuccessful],
                ['label' => 'Menunggu Verifikasi', 'value' => 'Rp '.number_format($totalPending, 0, ',', '.')." ({$countPending} transaksi)", 'raw' => $totalPending],
                ['label' => 'Pembayaran Ditolak', 'value' => 'Rp '.number_format($totalRejected, 0, ',', '.')." ({$countRejected} transaksi)", 'raw' => $totalRejected],
            ],
            'tableHeaders' => [
                'No. Pembayaran',
                'No. Invoice',
                'Penghuni',
                'Properti',
                'Kamar',
                'Tgl Bayar',
                'Tgl Verifikasi',
                'Metode',
                'Nominal (Rp)',
                'Status',
                'Verifikator',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                '',
                '',
                '',
                '',
                '',
                '',
                count($tableRows).' Total Transaksi',
                $totalSuccessful + $totalPending + $totalRejected,
                'Berhasil: Rp '.number_format($totalSuccessful, 0, ',', '.'),
                '',
            ],
            'totalRowDisplay' => [
                'total_successful' => $totalSuccessful,
                'count_successful' => $countSuccessful,
                'total_pending' => $totalPending,
                'count_pending' => $countPending,
                'total_rejected' => $totalRejected,
                'count_rejected' => $countRejected,
            ],
        ];
    }

    /**
     * C. Laporan Tagihan dan Tunggakan (Bills & Arrears).
     *
     * @return array<string, mixed>
     */
    protected function buildBillsReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $query = Bill::where(function ($sq) use ($owner, $propertyId) {
            $sq->whereHas('contract.property', function ($pq) use ($owner, $propertyId) {
                $pq->where('owner_id', $owner->id);
                if ($propertyId) {
                    $pq->where('id', $propertyId);
                }
            })->orWhereHas('room.property', function ($pq) use ($owner, $propertyId) {
                $pq->where('owner_id', $owner->id);
                if ($propertyId) {
                    $pq->where('id', $propertyId);
                }
            });
        })
            ->where('status', '!=', BillStatus::Dibatalkan->value)
            ->whereBetween('issue_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($statusFilter) {
            if ($statusFilter === 'overdue' || $statusFilter === 'jatuh_tempo') {
                $query->whereIn('status', [BillStatus::MenungguPembayaran->value, BillStatus::Sebagian->value, BillStatus::JatuhTempo->value])
                    ->where('due_date', '<', now()->startOfDay());
            } elseif ($statusFilter === 'unpaid') {
                $query->whereIn('status', [BillStatus::MenungguPembayaran->value, BillStatus::Sebagian->value, BillStatus::JatuhTempo->value]);
            } elseif (BillStatus::tryFrom($statusFilter)) {
                $query->where('status', $statusFilter);
            }
        }

        $bills = $query->with(['contract.property', 'room.property', 'user'])
            ->orderBy('due_date', 'asc')
            ->get();

        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        $totalOutstanding = 0.0;
        $totalOverdue = 0.0;

        $tableRows = [];
        $today = now()->startOfDay();
        $index = 1;

        foreach ($bills as $bill) {
            $prop = $bill->contract?->property ?? $bill->room?->property;
            $roomNumber = $bill->room?->room_number ?? $bill->contract?->room?->room_number ?? '-';
            $billTotal = (float) $bill->total_amount;
            $billPaid = (float) $bill->paid_amount;
            $outstanding = $bill->outstandingAmount();

            $totalInvoiced += $billTotal;
            $totalPaid += $billPaid;
            $totalOutstanding += $outstanding;

            $isOverdue = $bill->isOverdue();
            $daysLate = 0;
            if ($isOverdue && $bill->due_date) {
                $daysLate = (int) $today->diffInDays($bill->due_date->startOfDay());
                $totalOverdue += $outstanding;
            }

            $tableRows[] = [
                'index' => $index++,
                'bill_number' => $bill->bill_number,
                'tenant_name' => $bill->user?->name ?? 'Penghuni',
                'property_name' => $prop?->name ?? '-',
                'room_number' => $roomNumber,
                'type' => $bill->typeEnum()?->label() ?? 'Sewa',
                'issue_date' => $bill->issue_date?->format('d/m/Y') ?? '-',
                'due_date' => $bill->due_date?->format('d/m/Y') ?? '-',
                'total_amount' => $billTotal,
                'paid_amount' => $billPaid,
                'outstanding' => $outstanding,
                'status' => $bill->statusEnum()?->label() ?? ucfirst((string) $bill->status),
                'badge_variant' => $bill->statusBadgeVariant(),
                'is_overdue' => $isOverdue,
                'days_late' => $daysLate,
                'csvCells' => [
                    $bill->bill_number,
                    $bill->user?->name ?? 'Penghuni',
                    $prop?->name ?? '-',
                    $roomNumber,
                    $bill->typeEnum()?->label() ?? 'Sewa',
                    $bill->issue_date?->format('d/m/Y') ?? '-',
                    $bill->due_date?->format('d/m/Y') ?? '-',
                    $billTotal,
                    $billPaid,
                    $outstanding,
                    $bill->statusEnum()?->label() ?? ucfirst((string) $bill->status),
                    $daysLate > 0 ? $daysLate.' Hari' : '-',
                ],
            ];
        }

        return [
            'reportTitle' => 'Laporan Tagihan dan Tunggakan',
            'summary' => [
                ['label' => 'Total Nilai Tagihan Terbit', 'value' => 'Rp '.number_format($totalInvoiced, 0, ',', '.'), 'raw' => $totalInvoiced],
                ['label' => 'Total Pembayaran Diterima', 'value' => 'Rp '.number_format($totalPaid, 0, ',', '.'), 'raw' => $totalPaid],
                ['label' => 'Sisa Piutang Berjalan', 'value' => 'Rp '.number_format($totalOutstanding, 0, ',', '.'), 'raw' => $totalOutstanding],
                ['label' => 'Total Tunggakan Jatuh Tempo', 'value' => 'Rp '.number_format($totalOverdue, 0, ',', '.'), 'raw' => $totalOverdue],
            ],
            'tableHeaders' => [
                'No. Invoice',
                'Penghuni',
                'Properti',
                'Kamar',
                'Jenis Tagihan',
                'Tgl Terbit',
                'Jatuh Tempo',
                'Total Tagihan (Rp)',
                'Terbayar (Rp)',
                'Sisa Saldo (Rp)',
                'Status',
                'Keterlambatan',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                '',
                '',
                '',
                '',
                '',
                count($tableRows).' Invoice',
                $totalInvoiced,
                $totalPaid,
                $totalOutstanding,
                'Tunggakan: Rp '.number_format($totalOverdue, 0, ',', '.'),
                '',
            ],
            'totalRowDisplay' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'total_overdue' => $totalOverdue,
            ],
        ];
    }

    /**
     * D. Laporan Hunian (Occupancy & Rooms List).
     *
     * @return array<string, mixed>
     */
    protected function buildOccupancyReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $query = Room::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        });

        if ($statusFilter && in_array($statusFilter, ['kosong', 'terisi', 'perbaikan'], true)) {
            $query->where('status', $statusFilter);
        }

        $rooms = $query->with(['property', 'activeContract.tenant'])
            ->orderBy('property_id')
            ->orderBy('room_number')
            ->get();

        $totalRooms = $rooms->count();
        $occupiedCount = $rooms->where('status', 'terisi')->count();
        $vacantCount = $rooms->where('status', 'kosong')->count();
        $maintenanceCount = $rooms->where('status', 'perbaikan')->count();

        // Safely calculate occupancy rate (0% when no rooms exist, never division by zero)
        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedCount / $totalRooms) * 100) : 0;

        $tableRows = [];
        $index = 1;

        foreach ($rooms as $room) {
            $contract = $room->activeContract;
            $tenantName = $contract?->tenant?->name ?? '-';
            $periodStr = $contract
                ? ($contract->start_date?->format('d/m/Y').' s/d '.($contract->end_date?->format('d/m/Y') ?? 'Seterusnya'))
                : '-';

            $statusBadgeVariant = match ($room->status) {
                'terisi' => 'success',
                'kosong' => 'neutral',
                'perbaikan' => 'warning',
                default => 'default',
            };

            $statusLabel = match ($room->status) {
                'terisi' => 'Terisi',
                'kosong' => 'Kosong',
                'perbaikan' => 'Perbaikan',
                default => ucfirst((string) $room->status),
            };

            $tableRows[] = [
                'index' => $index++,
                'property_name' => $room->property?->name ?? '-',
                'room_number' => $room->room_number,
                'floor_type' => 'Lt. '.($room->floor ?? 1).' • '.ucfirst((string) $room->type),
                'price' => (float) $room->price,
                'status' => $statusLabel,
                'status_raw' => $room->status,
                'badge_variant' => $statusBadgeVariant,
                'tenant_name' => $tenantName,
                'contract_period' => $periodStr,
                'csvCells' => [
                    $room->property?->name ?? '-',
                    $room->room_number,
                    'Lantai '.($room->floor ?? 1),
                    ucfirst((string) $room->type),
                    (float) $room->price,
                    $statusLabel,
                    $tenantName,
                    $periodStr,
                ],
            ];
        }

        return [
            'reportTitle' => 'Laporan Okupansi dan Status Hunian',
            'summary' => [
                ['label' => 'Total Kamar', 'value' => (string) $totalRooms, 'raw' => $totalRooms],
                ['label' => 'Kamar Terisi', 'value' => "{$occupiedCount} Kamar ({$occupancyRate}%)", 'raw' => $occupiedCount],
                ['label' => 'Kamar Kosong', 'value' => "{$vacantCount} Kamar", 'raw' => $vacantCount],
                ['label' => 'Kamar Perbaikan', 'value' => "{$maintenanceCount} Kamar", 'raw' => $maintenanceCount],
            ],
            'tableHeaders' => [
                'Properti',
                'No. Kamar',
                'Lantai',
                'Tipe Kamar',
                'Harga Sewa (Rp)',
                'Status Kamar',
                'Penghuni Aktif',
                'Periode Kontrak',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                $totalRooms.' Total Kamar',
                '',
                '',
                '',
                "Okupansi {$occupancyRate}%",
                "Terisi: {$occupiedCount} | Kosong: {$vacantCount}",
                '',
            ],
            'totalRowDisplay' => [
                'total_rooms' => $totalRooms,
                'occupied' => $occupiedCount,
                'vacant' => $vacantCount,
                'maintenance' => $maintenanceCount,
                'occupancy_rate' => $occupancyRate,
            ],
        ];
    }

    /**
     * E. Laporan Komplain (Complaints & Tickets).
     *
     * @return array<string, mixed>
     */
    protected function buildComplaintsReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $query = Complaint::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $complaints = $query->with(['property', 'room', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $totalCount = $complaints->count();
        $newCount = $complaints->where('status', 'baru')->count();
        $inProgressCount = $complaints->whereIn('status', ['diproses', 'menunggu_tanggapan'])->count();
        $resolvedCount = $complaints->whereIn('status', ['selesai', 'ditutup'])->count();

        $tableRows = [];
        $index = 1;

        foreach ($complaints as $c) {
            $tableRows[] = [
                'index' => $index++,
                'ticket_number' => $c->ticket_number,
                'date' => $c->created_at?->format('d/m/Y H:i') ?? '-',
                'category' => $c->categoryLabel(),
                'title' => $c->title,
                'status' => $c->statusLabel(),
                'status_raw' => $c->status,
                'badge_variant' => $c->statusBadgeVariant(),
                'priority' => $c->priorityLabel(),
                'priority_variant' => $c->priorityBadgeVariant(),
                'property_name' => $c->property?->name ?? '-',
                'room_number' => $c->room?->room_number ?? '-',
                'tenant_name' => $c->user?->name ?? '-',
                'csvCells' => [
                    $c->ticket_number,
                    $c->created_at?->format('d/m/Y H:i') ?? '-',
                    $c->categoryLabel(),
                    $c->title,
                    $c->statusLabel(),
                    $c->priorityLabel(),
                    $c->property?->name ?? '-',
                    $c->room?->room_number ?? '-',
                    $c->user?->name ?? '-',
                ],
            ];
        }

        return [
            'reportTitle' => 'Laporan Tiket dan Keluhan Penghuni',
            'summary' => [
                ['label' => 'Total Tiket Masuk', 'value' => (string) $totalCount, 'raw' => $totalCount],
                ['label' => 'Tiket Baru', 'value' => (string) $newCount, 'raw' => $newCount],
                ['label' => 'Sedang Diproses', 'value' => (string) $inProgressCount, 'raw' => $inProgressCount],
                ['label' => 'Selesai / Ditutup', 'value' => (string) $resolvedCount, 'raw' => $resolvedCount],
            ],
            'tableHeaders' => [
                'No. Tiket',
                'Tanggal',
                'Kategori',
                'Judul Keluhan',
                'Status',
                'Prioritas',
                'Properti',
                'Kamar',
                'Penghuni',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                $totalCount.' Tiket',
                '',
                '',
                "Selesai: {$resolvedCount}",
                'Aktif: '.($newCount + $inProgressCount),
                '',
                '',
                '',
            ],
            'totalRowDisplay' => [
                'total' => $totalCount,
                'new' => $newCount,
                'in_progress' => $inProgressCount,
                'resolved' => $resolvedCount,
            ],
        ];
    }

    /**
     * F. Laporan Fasilitas dan Inventaris (Assets & Facilities).
     *
     * @return array<string, mixed>
     */
    protected function buildInventoryReport(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $statusFilter
    ): array {
        $query = Inventory::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        });

        if ($statusFilter) {
            $query->where('condition', $statusFilter);
        }

        $items = $query->with(['property', 'room'])
            ->orderBy('property_id')
            ->orderBy('name')
            ->get();

        $totalItemsCount = 0;
        $totalAssetValue = 0.0;
        $goodConditionCount = 0;
        $damagedConditionCount = 0;

        $tableRows = [];
        $index = 1;

        foreach ($items as $item) {
            $qty = (int) ($item->quantity ?? 1);
            $price = (float) ($item->purchase_price ?? 0);
            $subtotal = $price * $qty;

            $totalItemsCount += $qty;
            $totalAssetValue += $subtotal;

            if (in_array($item->condition, ['baik', 'baru'], true)) {
                $goodConditionCount += $qty;
            } else {
                $damagedConditionCount += $qty;
            }

            $location = $item->room ? ('Kamar '.$item->room->room_number) : ($item->location ?? 'Area Bersama');

            $tableRows[] = [
                'index' => $index++,
                'name' => $item->name,
                'serial_number' => $item->serial_number ?? '-',
                'category' => $item->category ?? 'Lainnya',
                'property_name' => $item->property?->name ?? '-',
                'location' => $location,
                'condition' => $item->condition_label,
                'condition_raw' => $item->condition,
                'badge_variant' => $item->condition_badge_variant,
                'quantity' => $qty,
                'purchase_price' => $price,
                'subtotal' => $subtotal,
                'purchase_date' => $item->purchase_date?->format('d/m/Y') ?? '-',
                'csvCells' => [
                    $item->name,
                    $item->serial_number ?? '-',
                    $item->category ?? 'Lainnya',
                    $item->property?->name ?? '-',
                    $location,
                    $item->condition_label,
                    $qty,
                    $price,
                    $subtotal,
                    $item->purchase_date?->format('d/m/Y') ?? '-',
                ],
            ];
        }

        return [
            'reportTitle' => 'Laporan Inventaris dan Aset Fasilitas',
            'summary' => [
                ['label' => 'Total Unit Aset', 'value' => (string) $totalItemsCount.' Unit', 'raw' => $totalItemsCount],
                ['label' => 'Estimasi Nilai Total Aset', 'value' => 'Rp '.number_format($totalAssetValue, 0, ',', '.'), 'raw' => $totalAssetValue],
                ['label' => 'Kondisi Baik/Baru', 'value' => (string) $goodConditionCount.' Unit', 'raw' => $goodConditionCount],
                ['label' => 'Kondisi Butuh Perhatian', 'value' => (string) $damagedConditionCount.' Unit', 'raw' => $damagedConditionCount],
            ],
            'tableHeaders' => [
                'Nama Aset',
                'No. Seri',
                'Kategori',
                'Properti',
                'Lokasi / Kamar',
                'Kondisi',
                'Qty',
                'Harga Satuan (Rp)',
                'Total Nilai (Rp)',
                'Tgl Pembelian',
            ],
            'tableRows' => $tableRows,
            'totalRowCsv' => [
                'TOTAL',
                count($tableRows).' Item Terdaftar',
                '',
                '',
                '',
                "Baik: {$goodConditionCount} | Rusak: {$damagedConditionCount}",
                $totalItemsCount,
                '',
                $totalAssetValue,
                '',
            ],
            'totalRowDisplay' => [
                'total_units' => $totalItemsCount,
                'total_value' => $totalAssetValue,
                'good_units' => $goodConditionCount,
                'damaged_units' => $damagedConditionCount,
            ],
        ];
    }

    /**
     * Base payments query strictly scoped to owner's properties and verified status.
     *
     * @return Builder<Payment>
     */
    protected function baseVerifiedPaymentQuery(User $owner, ?int $propertyId): Builder
    {
        return Payment::where('status', PaymentStatus::Berhasil->value)
            ->whereHas('bill', function ($bq) use ($owner, $propertyId) {
                $bq->where(function ($sq) use ($owner, $propertyId) {
                    $sq->whereHas('contract.property', function ($pq) use ($owner, $propertyId) {
                        $pq->where('owner_id', $owner->id);
                        if ($propertyId) {
                            $pq->where('id', $propertyId);
                        }
                    })->orWhereHas('room.property', function ($pq) use ($owner, $propertyId) {
                        $pq->where('owner_id', $owner->id);
                        if ($propertyId) {
                            $pq->where('id', $propertyId);
                        }
                    });
                });
            });
    }

    /**
     * Resolve start and end dates from request filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveReportDates(array $filters): array
    {
        // 1. Explicit start_date and end_date
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = Carbon::parse((string) $filters['start_date'])->startOfDay();
            $end = Carbon::parse((string) $filters['end_date'])->endOfDay();

            if ($start->gt($end)) {
                $temp = $start;
                $start = $end->copy()->startOfDay();
                $end = $temp->copy()->endOfDay();
            }

            $label = $start->locale('id')->translatedFormat('d M Y').' - '.$end->locale('id')->translatedFormat('d M Y');

            return [$start, $end, $label];
        }

        // 2. Period Type and Period parameter (aligned with AnalyticsService)
        $periodType = $filters['period_type'] ?? 'month';
        $periodInput = $filters['period'] ?? null;
        $now = now();

        if ($periodType === 'quarter') {
            $year = (int) $now->format('Y');
            $quarter = (int) ceil((int) $now->format('n') / 3);

            if ($periodInput && preg_match('/^(\d{4})-Q([1-4])$/', trim((string) $periodInput), $matches)) {
                $year = (int) $matches[1];
                $quarter = (int) $matches[2];
            }

            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;

            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();
            $periodLabel = "Triwulan {$quarter} {$year} ({$startDate->locale('id')->translatedFormat('M')} - {$endDate->locale('id')->translatedFormat('M')})";

            return [$startDate, $endDate, $periodLabel];
        }

        if ($periodType === 'year') {
            $year = (int) $now->format('Y');
            if ($periodInput && preg_match('/^\d{4}$/', trim((string) $periodInput))) {
                $year = (int) $periodInput;
            }

            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
            $periodLabel = "Tahun {$year}";

            return [$startDate, $endDate, $periodLabel];
        }

        // Default Monthly
        $key = $now->format('Y-m');
        if ($periodInput && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim((string) $periodInput))) {
            $key = trim((string) $periodInput);
        }

        $startDate = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $key)->endOfMonth();
        $periodLabel = $startDate->locale('id')->translatedFormat('F Y');

        return [$startDate, $endDate, $periodLabel];
    }

    /**
     * Normalize report type identifier.
     */
    protected function normalizeReportType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'pendapatan', 'revenue' => 'revenue',
            'pembayaran', 'payments' => 'payments',
            'tagihan', 'tunggakan', 'bills' => 'bills',
            'hunian', 'occupancy' => 'occupancy',
            'komplain', 'complaints' => 'complaints',
            'inventaris', 'fasilitas', 'inventory' => 'inventory',
            default => 'revenue',
        };
    }

    /**
     * Generate safe, descriptive filename for CSV download.
     */
    protected function generateCsvFilename(string $reportType, Carbon $startDate, Carbon $endDate): string
    {
        $dateSlug = $startDate->format('Ymd').'-'.$endDate->format('Ymd');

        return "huni-laporan-{$reportType}-{$dateSlug}.csv";
    }
}
