<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Get complete dashboard analytics payload for an owner.
     *
     * @param  array{period_type?: string, period?: string, property_id?: int|string|null}  $filters
     * @return array<string, mixed>
     */
    public function getAnalyticsData(User $owner, array $filters = []): array
    {
        $propertyId = ! empty($filters['property_id']) ? (int) $filters['property_id'] : null;
        $periodType = in_array($filters['period_type'] ?? '', ['month', 'quarter', 'year'], true)
            ? (string) $filters['period_type']
            : 'month';

        [$startDate, $endDate, $periodKey, $periodLabel] = $this->resolvePeriodDates(
            $periodType,
            $filters['period'] ?? null
        );

        [$prevStartDate, $prevEndDate] = $this->resolvePreviousPeriodDates($periodType, $startDate);

        // 1. KPI Calculations
        $kpis = $this->calculateKpis($owner, $propertyId, $startDate, $endDate, $prevStartDate, $prevEndDate);

        // 2. Trailing 12-Month Charts (ending at the selected period's end date)
        $revenueTrend = $this->getTrailing12MonthsRevenue($owner, $propertyId, $endDate);
        $occupancyTrend = $this->getTrailing12MonthsOccupancy($owner, $propertyId, $endDate);

        // 3. Distribution Donut Charts for the selected period
        $paymentStatus = $this->getPaymentStatusDistribution($owner, $propertyId, $startDate, $endDate);
        $revenueComposition = $this->getRevenueComposition($owner, $propertyId, $startDate, $endDate);

        // 4. Data Widgets
        $topOverdueTenants = $this->getTopOverdueTenants($owner, $propertyId, 5);
        $topComplaintCategories = $this->getTopComplaintCategories($owner, $propertyId, $startDate, $endDate);

        // 5. Filter Dropdown Options
        $filterOptions = $this->buildFilterOptions($owner, $periodType, $periodKey);

        return [
            'owner' => $owner,
            'periodType' => $periodType,
            'periodKey' => $periodKey,
            'periodLabel' => $periodLabel,
            'selectedPropertyId' => $propertyId,
            'kpis' => $kpis,
            'charts' => [
                'revenueTrend' => $revenueTrend,
                'occupancyTrend' => $occupancyTrend,
                'paymentStatus' => $paymentStatus,
                'revenueComposition' => $revenueComposition,
            ],
            'widgets' => [
                'topOverdueTenants' => $topOverdueTenants,
                'topComplaintCategories' => $topComplaintCategories,
            ],
            'filterOptions' => $filterOptions,
        ];
    }

    /**
     * Calculate core KPI figures.
     *
     * @return array<string, mixed>
     */
    protected function calculateKpis(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $prevStartDate,
        Carbon $prevEndDate
    ): array {
        // A. Total Verified Cash Revenue
        $totalRevenue = $this->getOwnerVerifiedRevenue($owner, $startDate, $endDate, $propertyId);
        $prevRevenue = $this->getOwnerVerifiedRevenue($owner, $prevStartDate, $prevEndDate, $propertyId);

        $revenueGrowth = null;
        if ($prevRevenue > 0) {
            $revenueGrowth = round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1);
        }

        // B. Invoices Paid
        $billQuery = $this->baseBillQuery($owner, $propertyId)
            ->whereBetween('issue_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $totalInvoicesCount = (clone $billQuery)->count();
        $paidInvoicesCount = (clone $billQuery)->where('status', BillStatus::Lunas->value)->count();
        $paidInvoicesRate = $totalInvoicesCount > 0 ? (int) round(($paidInvoicesCount / $totalInvoicesCount) * 100) : 0;

        // C. Room Occupancy
        $roomCounts = Room::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        })->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'terisi' then 1 end) as occupied")
            ->selectRaw("count(case when status = 'kosong' then 1 end) as vacant")
            ->selectRaw("count(case when status = 'perbaikan' then 1 end) as maintenance")
            ->first();

        $totalRooms = (int) ($roomCounts?->total ?? 0);
        $occupiedRooms = (int) ($roomCounts?->occupied ?? 0);
        $vacantRooms = (int) ($roomCounts?->vacant ?? 0);
        $maintenanceRooms = (int) ($roomCounts?->maintenance ?? 0);
        $occupancyRate = $totalRooms > 0 ? (int) round(($occupiedRooms / $totalRooms) * 100) : 0;

        // D. Overdue Arrears (Tunggakan yang sudah melewati jatuh tempo)
        $today = now()->startOfDay();
        $overdueQuery = $this->baseBillQuery($owner, $propertyId)
            ->whereIn('status', [BillStatus::MenungguPembayaran->value, BillStatus::Sebagian->value, BillStatus::JatuhTempo->value])
            ->where('due_date', '<', $today);

        $totalOverdueAmount = (float) ((clone $overdueQuery)->selectRaw('SUM(total_amount - paid_amount) as total')->value('total') ?? 0);
        $overdueTenantsCount = (int) ((clone $overdueQuery)->distinct('user_id')->count('user_id'));

        $totalActiveTenants = (int) Contract::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        })->where('status', 'aktif')->distinct('user_id')->count('user_id');

        $overdueTenantRate = $totalActiveTenants > 0 ? (int) round(($overdueTenantsCount / $totalActiveTenants) * 100) : 0;

        return [
            'totalRevenue' => $totalRevenue,
            'prevRevenue' => $prevRevenue,
            'revenueGrowth' => $revenueGrowth,
            'totalInvoicesCount' => $totalInvoicesCount,
            'paidInvoicesCount' => $paidInvoicesCount,
            'paidInvoicesRate' => $paidInvoicesRate,
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'vacantRooms' => $vacantRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupancyRate' => $occupancyRate,
            'totalOverdueAmount' => $totalOverdueAmount,
            'overdueTenantsCount' => $overdueTenantsCount,
            'totalActiveTenants' => $totalActiveTenants,
            'overdueTenantRate' => $overdueTenantRate,
        ];
    }

    /**
     * Trailing 12-month revenue trend stacked bar data.
     *
     * @return array<string, mixed>
     */
    public function getTrailing12MonthsRevenue(User $owner, ?int $propertyId, Carbon $endDate): array
    {
        $windowEnd = $endDate->copy()->endOfMonth();
        $windowStart = $windowEnd->copy()->subMonths(11)->startOfMonth();

        // 1. Fetch verified payments in the 12-month window
        $payments = $this->basePaymentQuery($owner, $propertyId)
            ->with('bill')
            ->where(function ($dq) use ($windowStart, $windowEnd) {
                $dq->where(function ($q1) use ($windowStart, $windowEnd) {
                    $q1->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$windowStart, $windowEnd]);
                })->orWhere(function ($q2) use ($windowStart, $windowEnd) {
                    $q2->whereNull('verified_at')
                        ->whereBetween('payment_date', [$windowStart, $windowEnd]);
                });
            })
            ->get();

        // 2. Fetch bills in the 12-month window to calculate overdue amounts
        $bills = $this->baseBillQuery($owner, $propertyId)
            ->whereBetween('issue_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->get();

        $categories = [];
        $onTimeSeries = [];
        $lateSeries = [];
        $overdueSeries = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $windowEnd->copy()->subMonths($i);
            $key = $month->format('Y-m');
            $categories[] = $month->locale('id')->translatedFormat('M');

            // Filter payments recognized in this month
            $monthPayments = $payments->filter(function (Payment $p) use ($key) {
                $recDate = $p->verified_at ?? $p->payment_date;

                return $recDate && $recDate->format('Y-m') === $key;
            });

            $onTimeAmount = 0.0;
            $lateAmount = 0.0;
            foreach ($monthPayments as $p) {
                $bill = $p->bill;
                $isLate = $bill && $bill->due_date && $p->payment_date && $p->payment_date->gt($bill->due_date->endOfDay());
                if ($isLate) {
                    $lateAmount += (float) $p->amount;
                } else {
                    $onTimeAmount += (float) $p->amount;
                }
            }

            // Overdue amount for bills issued in this month
            $monthOverdue = 0.0;
            $monthBills = $bills->filter(fn (Bill $b) => $b->issue_date && $b->issue_date->format('Y-m') === $key);
            foreach ($monthBills as $b) {
                if ($b->isOverdue()) {
                    $monthOverdue += $b->outstandingAmount();
                }
            }

            $onTimeSeries[] = (int) round($onTimeAmount);
            $lateSeries[] = (int) round($lateAmount);
            $overdueSeries[] = (int) round($monthOverdue);
        }

        return [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Pembayaran Tepat Waktu',
                    'data' => $onTimeSeries,
                    'color' => '#C7F36B',
                ],
                [
                    'name' => 'Pembayaran Terlambat',
                    'data' => $lateSeries,
                    'color' => '#FFD966',
                ],
                [
                    'name' => 'Tunggakan',
                    'data' => $overdueSeries,
                    'color' => '#FFB7C5',
                ],
            ],
        ];
    }

    /**
     * Trailing 12-month occupancy rate area/line chart data.
     *
     * @return array<string, mixed>
     */
    public function getTrailing12MonthsOccupancy(User $owner, ?int $propertyId, Carbon $endDate): array
    {
        $windowEnd = $endDate->copy()->endOfMonth();
        $windowStart = $windowEnd->copy()->subMonths(11)->startOfMonth();

        $totalRooms = (int) Room::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        })->count();

        // Contracts overlapping the 12-month period
        $contracts = Contract::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        })
            ->where('start_date', '<=', $windowEnd->toDateString())
            ->where(function ($q) use ($windowStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $windowStart->toDateString());
            })
            ->whereIn('status', ['aktif', 'selesai'])
            ->get();

        $categories = [];
        $occupancySeries = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $windowEnd->copy()->subMonths($i);
            $mStart = $month->copy()->startOfMonth()->toDateString();
            $mEnd = $month->copy()->endOfMonth()->toDateString();

            $categories[] = $month->locale('id')->translatedFormat('M');

            if ($totalRooms <= 0) {
                $occupancySeries[] = 0;

                continue;
            }

            // Count distinct rooms that had an active contract during this month
            $occupiedInMonth = $contracts->filter(function (Contract $c) use ($mStart, $mEnd) {
                $startsBeforeEnd = $c->start_date && $c->start_date->lte($mEnd);
                $endsAfterStart = ! $c->end_date || $c->end_date->gte($mStart);

                return $startsBeforeEnd && $endsAfterStart;
            })->pluck('room_id')->unique()->count();

            $rate = min(100, (int) round(($occupiedInMonth / $totalRooms) * 100));
            $occupancySeries[] = $rate;
        }

        return [
            'categories' => $categories,
            'totalRooms' => $totalRooms,
            'series' => [
                [
                    'name' => 'Tingkat Hunian',
                    'data' => $occupancySeries,
                    'color' => '#C7F36B',
                ],
            ],
        ];
    }

    /**
     * Payment status distribution donut chart data.
     *
     * @return array<string, mixed>
     */
    public function getPaymentStatusDistribution(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $bills = $this->baseBillQuery($owner, $propertyId)
            ->whereBetween('issue_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $paidCount = 0;
        $lateCount = 0;
        $unpaidCount = 0;

        $today = now()->startOfDay();
        foreach ($bills as $bill) {
            if ($bill->isPaid()) {
                $paidCount++;
            } elseif ($bill->isOverdue()) {
                $lateCount++;
            } else {
                $unpaidCount++;
            }
        }

        $total = $paidCount + $lateCount + $unpaidCount;

        $paidPct = $total > 0 ? (int) round(($paidCount / $total) * 100) : 0;
        $latePct = $total > 0 ? (int) round(($lateCount / $total) * 100) : 0;
        $unpaidPct = $total > 0 ? max(0, 100 - $paidPct - $latePct) : 0;

        return [
            'total' => $total,
            'labels' => ['Dibayar', 'Terlambat', 'Belum Dibayar'],
            'series' => [$paidCount, $lateCount, $unpaidCount],
            'colors' => ['#C7F36B', '#FFD966', '#FFB7C5'],
            'counts' => [
                'paid' => $paidCount,
                'late' => $lateCount,
                'unpaid' => $unpaidCount,
            ],
            'percentages' => [
                'paid' => $paidPct,
                'late' => $latePct,
                'unpaid' => $unpaidPct,
            ],
        ];
    }

    /**
     * Revenue composition donut chart data by bill type.
     *
     * @return array<string, mixed>
     */
    public function getRevenueComposition(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $payments = $this->basePaymentQuery($owner, $propertyId)
            ->with('bill')
            ->where(function ($dq) use ($startDate, $endDate) {
                $dq->where(function ($q1) use ($startDate, $endDate) {
                    $q1->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$startDate, $endDate]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('verified_at')
                        ->whereBetween('payment_date', [$startDate, $endDate]);
                });
            })
            ->get();

        $rentAmount = 0.0;
        $depositAmount = 0.0;
        $additionalAmount = 0.0;
        $otherAmount = 0.0;

        foreach ($payments as $payment) {
            $bill = $payment->bill;
            $type = $bill?->type ?? BillType::Sewa->value;
            $amount = (float) $payment->amount;

            match ($type) {
                BillType::Sewa->value => $rentAmount += $amount,
                BillType::Deposit->value => $depositAmount += $amount,
                BillType::Tambahan->value => $additionalAmount += $amount,
                default => $otherAmount += $amount,
            };
        }

        $total = $rentAmount + $depositAmount + $additionalAmount + $otherAmount;

        $rentPct = $total > 0 ? (int) round(($rentAmount / $total) * 100) : 0;
        $depositPct = $total > 0 ? (int) round(($depositAmount / $total) * 100) : 0;
        $additionalPct = $total > 0 ? (int) round(($additionalAmount / $total) * 100) : 0;
        $otherPct = $total > 0 ? max(0, 100 - $rentPct - $depositPct - $additionalPct) : 0;

        return [
            'total' => $total,
            'labels' => ['Sewa Kamar', 'Deposit', 'Biaya Tambahan', 'Lainnya'],
            'series' => [
                (int) round($rentAmount),
                (int) round($depositAmount),
                (int) round($additionalAmount),
                (int) round($otherAmount),
            ],
            'colors' => ['#C7F36B', '#FFD966', '#B4E4FF', '#D8B4FE'],
            'amounts' => [
                'rent' => $rentAmount,
                'deposit' => $depositAmount,
                'additional' => $additionalAmount,
                'other' => $otherAmount,
            ],
            'percentages' => [
                'rent' => $rentPct,
                'deposit' => $depositPct,
                'additional' => $additionalPct,
                'other' => $otherPct,
            ],
        ];
    }

    /**
     * Top 5 tenants with highest overdue arrears.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopOverdueTenants(User $owner, ?int $propertyId, int $limit = 5): Collection
    {
        $today = now()->startOfDay();

        $overdueBills = $this->baseBillQuery($owner, $propertyId)
            ->whereIn('status', [BillStatus::MenungguPembayaran->value, BillStatus::Sebagian->value, BillStatus::JatuhTempo->value])
            ->where('due_date', '<', $today)
            ->with(['user', 'room'])
            ->get();

        return $overdueBills
            ->groupBy('user_id')
            ->map(function (Collection $userBills) use ($today) {
                /** @var Bill $firstBill */
                $firstBill = $userBills->first();
                $user = $firstBill->user;

                $totalOverdue = (float) $userBills->sum(fn (Bill $b) => $b->outstandingAmount());
                $maxDaysLate = (int) $userBills->max(fn (Bill $b) => $b->due_date ? $today->diffInDays($b->due_date->startOfDay()) : 0);
                $roomNumber = $firstBill->room?->room_number ?? '-';

                return [
                    'user_id' => $user?->id,
                    'name' => $user?->name ?? 'Penghuni',
                    'room_number' => $roomNumber,
                    'total_overdue' => $totalOverdue,
                    'days_late' => $maxDaysLate,
                ];
            })
            ->filter(fn (array $item) => $item['total_overdue'] > 0)
            ->sortByDesc('total_overdue')
            ->take($limit)
            ->values();
    }

    /**
     * Top complaint categories breakdown.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopComplaintCategories(
        User $owner,
        ?int $propertyId,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $complaintQuery = Complaint::whereHas('property', function ($pq) use ($owner, $propertyId) {
            $pq->where('owner_id', $owner->id);
            if ($propertyId) {
                $pq->where('id', $propertyId);
            }
        });

        // Filter by date range; fallback to trailing 6 months if period has no complaints
        $periodComplaints = (clone $complaintQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($periodComplaints->isEmpty()) {
            $periodComplaints = (clone $complaintQuery)
                ->where('created_at', '>=', now()->subMonths(6))
                ->get();
        }

        $totalComplaints = $periodComplaints->count();
        if ($totalComplaints <= 0) {
            return collect();
        }

        return $periodComplaints
            ->groupBy('category')
            ->map(function (Collection $items, string $category) use ($totalComplaints) {
                $count = $items->count();
                $percentage = (int) round(($count / $totalComplaints) * 100);

                return [
                    'category' => $category,
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Get owner verified cash revenue in a given date range.
     */
    public function getOwnerVerifiedRevenue(
        User $owner,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?int $propertyId = null
    ): float {
        return (float) $this->basePaymentQuery($owner, $propertyId)
            ->where(function ($dq) use ($startDate, $endDate) {
                $dq->where(function ($q1) use ($startDate, $endDate) {
                    $q1->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$startDate, $endDate]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('verified_at')
                        ->whereBetween('payment_date', [$startDate, $endDate]);
                });
            })
            ->sum('amount');
    }

    /**
     * Base payments query strictly scoped to owner's properties and verified status.
     *
     * @return Builder<Payment>
     */
    protected function basePaymentQuery(User $owner, ?int $propertyId): Builder
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
     * Base bills query strictly scoped to owner's properties (excluding cancelled bills).
     *
     * @return Builder<Bill>
     */
    protected function baseBillQuery(User $owner, ?int $propertyId): Builder
    {
        return Bill::where(function ($sq) use ($owner, $propertyId) {
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
        })->where('status', '!=', BillStatus::Dibatalkan->value);
    }

    /**
     * Resolve start date, end date, key, and label from period type and input.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    protected function resolvePeriodDates(string $periodType, ?string $periodInput): array
    {
        $now = now();

        if ($periodType === 'quarter') {
            $year = (int) $now->format('Y');
            $quarter = (int) ceil((int) $now->format('n') / 3);

            if ($periodInput && preg_match('/^(\d{4})-Q([1-4])$/', trim($periodInput), $matches)) {
                $year = (int) $matches[1];
                $quarter = (int) $matches[2];
            }

            $key = "{$year}-Q{$quarter}";
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;

            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();
            $periodLabel = "Triwulan {$quarter} {$year} ({$startDate->locale('id')->translatedFormat('M')} - {$endDate->locale('id')->translatedFormat('M')})";

            return [$startDate, $endDate, $key, $periodLabel];
        }

        if ($periodType === 'year') {
            $year = (int) $now->format('Y');
            if ($periodInput && preg_match('/^\d{4}$/', trim($periodInput))) {
                $year = (int) $periodInput;
            }

            $key = (string) $year;
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
            $periodLabel = "Tahun {$year}";

            return [$startDate, $endDate, $key, $periodLabel];
        }

        // Default: monthly
        $key = $now->format('Y-m');
        if ($periodInput && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim($periodInput))) {
            $key = trim($periodInput);
        }

        $startDate = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $key)->endOfMonth();
        $periodLabel = $startDate->locale('id')->translatedFormat('F Y');

        return [$startDate, $endDate, $key, $periodLabel];
    }

    /**
     * Resolve date range for the previous comparative period.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePreviousPeriodDates(string $periodType, Carbon $startDate): array
    {
        if ($periodType === 'quarter') {
            $prevStart = $startDate->copy()->subMonths(3)->startOfMonth();
            $prevEnd = $startDate->copy()->subDay()->endOfMonth();

            return [$prevStart, $prevEnd];
        }

        if ($periodType === 'year') {
            $prevStart = $startDate->copy()->subYear()->startOfYear();
            $prevEnd = $startDate->copy()->subYear()->endOfYear();

            return [$prevStart, $prevEnd];
        }

        // Monthly
        $prevStart = $startDate->copy()->subMonth()->startOfMonth();
        $prevEnd = $startDate->copy()->subMonth()->endOfMonth();

        return [$prevStart, $prevEnd];
    }

    /**
     * Build list of filter dropdown options.
     *
     * @return array<string, mixed>
     */
    protected function buildFilterOptions(User $owner, string $periodType, string $currentKey): array
    {
        $properties = $owner->properties()->orderBy('name')->get(['id', 'name', 'city']);

        $monthlyOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $monthlyOptions[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('F Y'),
            ];
        }

        $quarterlyOptions = [];
        $currentYear = (int) now()->format('Y');
        $currentQ = (int) ceil((int) now()->format('n') / 3);
        for ($i = 0; $i < 8; $i++) {
            $q = $currentQ - $i;
            $y = $currentYear;
            while ($q <= 0) {
                $q += 4;
                $y -= 1;
            }
            $val = "{$y}-Q{$q}";
            $startMonth = Carbon::create($y, ($q - 1) * 3 + 1, 1)->locale('id')->translatedFormat('M');
            $endMonth = Carbon::create($y, $q * 3, 1)->locale('id')->translatedFormat('M');
            $quarterlyOptions[] = [
                'value' => $val,
                'label' => "Triwulan {$q} {$y} ({$startMonth} - {$endMonth})",
            ];
        }

        $yearlyOptions = [];
        for ($i = 0; $i < 5; $i++) {
            $y = $currentYear - $i;
            $yearlyOptions[] = [
                'value' => (string) $y,
                'label' => "Tahun {$y}",
            ];
        }

        return [
            'properties' => $properties,
            'monthly' => $monthlyOptions,
            'quarterly' => $quarterlyOptions,
            'yearly' => $yearlyOptions,
            'currentPeriodType' => $periodType,
            'currentKey' => $currentKey,
        ];
    }
}
