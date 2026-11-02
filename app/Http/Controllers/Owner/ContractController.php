<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RenewContractRequest;
use App\Http\Requests\Owner\StoreContractDocumentRequest;
use App\Http\Requests\Owner\StoreContractRequest;
use App\Http\Requests\Owner\UpdateContractRequest;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\BillService;
use App\Services\NotificationService;
use App\Services\RoomOccupancyService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(
        protected RoomOccupancyService $occupancyService,
        protected BillService $billService
    ) {}

    /**
     * Display a listing of contracts owned by the authenticated owner.
     */
    public function index(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        $query = Contract::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->with(['property', 'room', 'tenant', 'documents']);

        // Search query: contract_number, tenant name, property name, room number
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('property', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($rq) => $rq->where('room_number', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            $query->where('status', $status);
        }

        // Property filter
        if ($request->filled('property_id')) {
            $propertyId = (int) $request->input('property_id');
            $query->where('property_id', $propertyId);
        }

        // Statistics counts
        $baseQuery = Contract::whereHas('property', fn ($q) => $q->where('owner_id', $owner->id));
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'aktif' => (clone $baseQuery)->where('status', 'aktif')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
            'dibatalkan' => (clone $baseQuery)->where('status', 'dibatalkan')->count(),
        ];

        $contracts = $query->latest('created_at')->paginate(10)->withQueryString();
        $properties = $owner->properties()->orderBy('name')->get();

        return view('owner.contracts.index', [
            'contracts' => $contracts,
            'stats' => $stats,
            'properties' => $properties,
            'selectedStatus' => $request->input('status', ''),
            'selectedProperty' => $request->input('property_id', ''),
            'searchQuery' => $request->input('q', ''),
        ]);
    }

    /**
     * Show the form for creating a new contract.
     */
    public function create(Request $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        // Get all properties belonging to owner with their rooms
        $properties = $owner->properties()->with(['rooms' => fn ($q) => $q->orderBy('room_number')])->orderBy('name')->get();

        // Find eligible tenants connected via accepted invitations or prior contracts across owner properties
        $acceptedInvitations = Invitation::where('invited_by', $owner->id)
            ->whereIn('status', ['diterima', 'accepted'])
            ->with(['room.property', 'user', 'tenant'])
            ->latest('accepted_at')
            ->get();

        // Preload structured options for Alpine.js dynamic dropdown filtering
        $roomsData = [];
        foreach ($properties as $prop) {
            foreach ($prop->rooms as $rm) {
                // Find eligible tenant(s) for this room
                $roomInvitations = $acceptedInvitations->where('room_id', $rm->id);
                $eligibleTenants = [];

                foreach ($roomInvitations as $inv) {
                    $tenantUser = $inv->user ?? $inv->tenant;
                    if ($tenantUser) {
                        $eligibleTenants[] = [
                            'id' => $tenantUser->id,
                            'name' => $tenantUser->name,
                            'email' => $tenantUser->email,
                            'phone' => $tenantUser->phone,
                        ];
                    }
                }

                // Also check existing/past tenants on this room
                $pastContracts = Contract::where('room_id', $rm->id)->with('tenant')->get();
                foreach ($pastContracts as $pc) {
                    if ($pc->tenant && ! collect($eligibleTenants)->contains('id', $pc->tenant->id)) {
                        $eligibleTenants[] = [
                            'id' => $pc->tenant->id,
                            'name' => $pc->tenant->name,
                            'email' => $pc->tenant->email,
                            'phone' => $pc->tenant->phone,
                        ];
                    }
                }

                $roomsData[] = [
                    'id' => $rm->id,
                    'property_id' => $rm->property_id,
                    'room_number' => $rm->room_number,
                    'price' => (float) $rm->price,
                    'status' => $rm->status,
                    'eligible_tenants' => $eligibleTenants,
                ];
            }
        }

        return view('owner.contracts.create', [
            'properties' => $properties,
            'roomsData' => $roomsData,
            'preselectedPropertyId' => (int) $request->input('property_id', 0),
            'preselectedRoomId' => (int) $request->input('room_id', 0),
            'preselectedUserId' => (int) $request->input('user_id', 0),
        ]);
    }

    /**
     * Store a newly created contract in storage.
     */
    public function store(StoreContractRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $roomId = (int) $validated['room_id'];
        $propertyId = (int) $validated['property_id'];
        $tenantId = (int) $validated['user_id'];
        $startDate = (string) $validated['start_date'];
        $endDate = (string) $validated['end_date'];
        $monthlyRent = (float) $validated['monthly_rent'];
        $status = (string) $validated['status'];

        $uploadedFilePath = null;

        try {
            $contract = DB::transaction(function () use ($request, $roomId, $propertyId, $tenantId, $startDate, $endDate, $monthlyRent, $status, $validated, &$uploadedFilePath) {
                // Lock room pessimistically to avoid race conditions (Audit Finding B-2)
                /** @var Room $room */
                $room = Room::where('id', $roomId)->lockForUpdate()->firstOrFail();

                // Revalidate room ownership inside transaction
                if ($room->property?->owner_id !== $request->user()?->id) {
                    abort(403, 'Anda tidak memiliki hak akses untuk menyewakan kamar pada properti ini.');
                }

                // If creating an active contract, recheck room eligibility and date overlaps under lock
                if ($status === 'aktif') {
                    if ($room->status === 'perbaikan') {
                        throw ValidationException::withMessages([
                            'room_id' => 'Kamar sedang dalam masa perbaikan dan tidak dapat disewakan saat ini.',
                        ]);
                    }

                    $overlapping = $this->occupancyService->getOverlappingContract($room, $startDate, $endDate);
                    if ($overlapping) {
                        throw ValidationException::withMessages([
                            'room_id' => "Periode sewa kamar bertabrakan dengan kontrak aktif lain ({$overlapping->contract_number}).",
                        ]);
                    }
                }

                // Calculate duration in months
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                $durationMonths = isset($validated['duration_months']) && (int) $validated['duration_months'] > 0
                    ? (int) $validated['duration_months']
                    : max(1, (int) ceil($start->floatDiffInMonths($end)));

                $totalAmount = round($durationMonths * $monthlyRent, 2);

                // Generate clean unique contract number
                $contractNumber = $this->generateContractNumber();

                // Create contract
                /** @var Contract $newContract */
                $newContract = Contract::create([
                    'contract_number' => $contractNumber,
                    'property_id' => $propertyId,
                    'room_id' => $roomId,
                    'user_id' => $tenantId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration_months' => $durationMonths,
                    'monthly_rent' => $monthlyRent,
                    'total_amount' => $totalAmount,
                    'deposit_amount' => (float) ($validated['deposit_amount'] ?? 0),
                    'deposit_status' => $validated['deposit_status'] ?? 'belum_dibayar',
                    'payment_method' => $validated['payment_method'] ?? 'transfer_bank',
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Handle optional initial document upload (Audit Finding C-3)
                if ($request->hasFile('document')) {
                    $file = $request->file('document');
                    $uploadedFilePath = Storage::disk('local')->putFile('contracts', $file);

                    ContractDocument::create([
                        'contract_id' => $newContract->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $uploadedFilePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }

                // Synchronize room occupancy
                $this->occupancyService->syncRoomStatus($room);

                // Generate initial rent and deposit invoices if created as active
                if ($status === 'aktif') {
                    $this->billService->generateInitialInvoices($newContract);
                }

                return $newContract;
            });
        } catch (\Throwable $e) {
            $this->safeDeleteUploadedFile($uploadedFilePath);
            throw $e;
        }

        if ($contract->isActive()) {
            app(NotificationService::class)->notifyContractActivated($contract);
        }

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Kontrak sewa '.$contract->contract_number.' berhasil dibuat.');
    }

    /**
     * Display the specified contract.
     */
    public function show(Contract $contract): View
    {
        Gate::authorize('view', $contract);

        $contract->load([
            'property',
            'room',
            'tenant',
            'documents',
            'bills',
        ]);

        return view('owner.contracts.show', [
            'contract' => $contract,
        ]);
    }

    /**
     * Show the form for editing the specified contract.
     */
    public function edit(Contract $contract): View|RedirectResponse
    {
        Gate::authorize('update', $contract);

        if ($contract->isCompleted() || $contract->isCancelled()) {
            return redirect()->route('owner.contracts.show', $contract)
                ->with('error', 'Kontrak dengan status '.$contract->status.' sudah terkunci dan tidak dapat diubah.');
        }

        $contract->load(['property', 'room', 'tenant']);

        return view('owner.contracts.edit', [
            'contract' => $contract,
        ]);
    }

    /**
     * Update the specified contract in storage.
     */
    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('update', $contract);

        $validated = $request->validated();

        $wasActivated = false;

        DB::transaction(function () use ($contract, $validated, &$wasActivated) {
            /** @var Room $room */
            $room = Room::where('id', $contract->room_id)->lockForUpdate()->firstOrFail();

            // Audit Finding B-4: Protect Financial History if bills exist
            if ($contract->bills()->exists()) {
                $endDateChanged = isset($validated['end_date']) && (string) $validated['end_date'] !== $contract->end_date?->toDateString();
                $depositChanged = isset($validated['deposit_amount']) && (float) $validated['deposit_amount'] !== (float) $contract->deposit_amount;
                $rentChanged = isset($validated['monthly_rent']) && (float) $validated['monthly_rent'] !== (float) $contract->monthly_rent;

                if ($endDateChanged || $depositChanged || $rentChanged) {
                    throw ValidationException::withMessages([
                        'end_date' => 'Kontrak ini telah memiliki riwayat tagihan/keuangan sehingga periode sewa dan ketentuan finansial tidak dapat diubah secara retroaktif.',
                    ]);
                }
            }

            $startDate = $contract->isDraft() && isset($validated['start_date'])
                ? (string) $validated['start_date']
                : $contract->start_date?->toDateString();

            $endDate = (string) $validated['end_date'];

            $monthlyRent = $contract->isDraft() && isset($validated['monthly_rent'])
                ? (float) $validated['monthly_rent']
                : (float) $contract->monthly_rent;

            $targetStatus = $contract->isDraft() && isset($validated['status'])
                ? (string) $validated['status']
                : $contract->status;

            // Audit Finding B-2: Recheck overlap inside transaction if contract is or becomes active
            if ($targetStatus === 'aktif' && $startDate && $endDate) {
                $overlapping = $this->occupancyService->getOverlappingContract($room, $startDate, $endDate, $contract->id);
                if ($overlapping) {
                    throw ValidationException::withMessages([
                        'end_date' => "Periode sewa bertabrakan dengan kontrak aktif lain ({$overlapping->contract_number}).",
                    ]);
                }
            }

            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $durationMonths = isset($validated['duration_months']) && (int) $validated['duration_months'] > 0
                ? (int) $validated['duration_months']
                : max(1, (int) ceil($start->floatDiffInMonths($end)));

            $totalAmount = round($durationMonths * $monthlyRent, 2);

            $updateData = [
                'end_date' => $endDate,
                'duration_months' => $durationMonths,
                'total_amount' => $totalAmount,
                'deposit_amount' => isset($validated['deposit_amount']) ? (float) $validated['deposit_amount'] : $contract->deposit_amount,
                'deposit_status' => $validated['deposit_status'] ?? $contract->deposit_status,
                'payment_method' => $validated['payment_method'] ?? $contract->payment_method,
                'notes' => $validated['notes'] ?? $contract->notes,
            ];

            $isActivating = $contract->isDraft() && $targetStatus === 'aktif';
            $wasActivated = $isActivating;

            if ($contract->isDraft()) {
                $updateData['start_date'] = $startDate;
                $updateData['monthly_rent'] = $monthlyRent;
                if (isset($validated['status'])) {
                    $updateData['status'] = $validated['status'];
                }
            }

            $contract->update($updateData);

            // Re-sync room occupancy
            $this->occupancyService->syncRoomStatus($room);

            // Generate initial invoices upon transition from draft to active
            if ($isActivating) {
                $this->billService->generateInitialInvoices($contract);
            }
        });

        if ($wasActivated) {
            app(NotificationService::class)->notifyContractActivated($contract);
        }

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Kontrak sewa '.$contract->contract_number.' berhasil diperbarui.');
    }

    /**
     * Terminate an active contract upon completion or early lease termination.
     */
    public function terminate(Request $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('update', $contract);

        if (! $contract->isActive()) {
            return redirect()->route('owner.contracts.show', $contract)
                ->with('error', 'Hanya kontrak aktif yang dapat diselesaikan.');
        }

        $request->validate([
            'termination_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($contract, $request) {
            /** @var Room $room */
            $room = Room::where('id', $contract->room_id)->lockForUpdate()->firstOrFail();

            $noteSuffix = "\n[Selesai pada ".now()->format('d/m/Y H:i').']';
            if ($request->filled('termination_notes')) {
                $noteSuffix .= ' Catatan: '.trim((string) $request->input('termination_notes'));
            }

            $contract->update([
                'status' => 'selesai',
                'notes' => trim(($contract->notes ?? '').$noteSuffix),
            ]);

            $this->occupancyService->syncRoomStatus($room);
        });

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Kontrak sewa '.$contract->contract_number.' telah ditandai selesai.');
    }

    /**
     * Cancel a draft or active contract.
     */
    public function cancel(Request $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('update', $contract);

        if ($contract->isCompleted() || $contract->isCancelled()) {
            return redirect()->route('owner.contracts.show', $contract)
                ->with('error', 'Kontrak dengan status '.$contract->status.' tidak dapat dibatalkan.');
        }

        $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($contract, $request) {
            /** @var Room $room */
            $room = Room::where('id', $contract->room_id)->lockForUpdate()->firstOrFail();

            $reason = $request->filled('cancellation_reason')
                ? trim((string) $request->input('cancellation_reason'))
                : 'Dibatalkan oleh Pemilik';

            $noteSuffix = "\n[Dibatalkan pada ".now()->format('d/m/Y H:i').": {$reason}]";

            $contract->update([
                'status' => 'dibatalkan',
                'notes' => trim(($contract->notes ?? '').$noteSuffix),
            ]);

            $this->occupancyService->syncRoomStatus($room);
        });

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Kontrak sewa '.$contract->contract_number.' telah dibatalkan.');
    }

    /**
     * Remove the specified contract from storage safely.
     * Prohibits deletion if any financial history (bills/invoices) exists.
     */
    public function destroy(Contract $contract): RedirectResponse
    {
        Gate::authorize('delete', $contract);

        if ($contract->bills()->exists()) {
            return redirect()->back()
                ->with('error', 'Kontrak sewa tidak dapat dihapus karena memiliki riwayat tagihan atau transaksi keuangan.');
        }

        $contractNumber = $contract->contract_number;
        $room = $contract->room;

        DB::transaction(function () use ($contract, $room) {
            $contract->delete();
            if ($room) {
                $this->occupancyService->syncRoomStatus($room);
            }
        });

        return redirect()->route('owner.contracts.index')
            ->with('success', "Kontrak sewa {$contractNumber} berhasil dihapus.");
    }

    /**
     * Renew an existing contract by creating a new chained contract.
     */
    public function renew(RenewContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('update', $contract);

        $validated = $request->validated();
        $startDate = (string) $validated['start_date'];
        $endDate = (string) $validated['end_date'];
        $monthlyRent = (float) $validated['monthly_rent'];

        $uploadedFilePath = null;

        try {
            $newContract = DB::transaction(function () use ($request, $contract, $startDate, $endDate, $monthlyRent, $validated, &$uploadedFilePath) {
                // Lock room pessimistically to avoid race conditions (Audit Finding B-2)
                /** @var Room $room */
                $room = Room::where('id', $contract->room_id)->lockForUpdate()->firstOrFail();

                // Revalidate room ownership & eligibility inside transaction
                if ($room->property?->owner_id !== $request->user()?->id) {
                    abort(403, 'Anda tidak memiliki hak akses untuk memperpanjang sewa kamar pada properti ini.');
                }

                if ($room->status === 'perbaikan') {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar sedang dalam masa perbaikan dan tidak dapat diperpanjang saat ini.',
                    ]);
                }

                // Check for overlapping active contracts excluding the source contract
                $overlapping = $this->occupancyService->getOverlappingContract($room, $startDate, $endDate, $contract->id);
                if ($overlapping) {
                    throw ValidationException::withMessages([
                        'start_date' => "Periode perpanjangan sewa bertabrakan dengan kontrak aktif lain ({$overlapping->contract_number}).",
                    ]);
                }

                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                $durationMonths = isset($validated['duration_months']) && (int) $validated['duration_months'] > 0
                    ? (int) $validated['duration_months']
                    : max(1, (int) ceil($start->floatDiffInMonths($end)));

                $totalAmount = round($durationMonths * $monthlyRent, 2);

                $contractNumber = $this->generateContractNumber();

                $created = Contract::create([
                    'contract_number' => $contractNumber,
                    'property_id' => $contract->property_id,
                    'room_id' => $contract->room_id,
                    'user_id' => $contract->user_id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration_months' => $durationMonths,
                    'monthly_rent' => $monthlyRent,
                    'total_amount' => $totalAmount,
                    'deposit_amount' => (float) ($validated['deposit_amount'] ?? 0),
                    'deposit_status' => $validated['deposit_status'] ?? 'belum_dibayar',
                    'payment_method' => $validated['payment_method'] ?? 'transfer_bank',
                    'status' => 'aktif',
                    'notes' => 'Perpanjangan dari kontrak '.$contract->contract_number.($validated['notes'] ? "\n".$validated['notes'] : ''),
                ]);

                // Handle document if uploaded during renewal (Audit Finding C-3)
                if ($request->hasFile('document')) {
                    $file = $request->file('document');
                    $uploadedFilePath = Storage::disk('local')->putFile('contracts', $file);

                    ContractDocument::create([
                        'contract_id' => $created->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $uploadedFilePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }

                // Sync room status
                $this->occupancyService->syncRoomStatus($room);

                // Generate initial rent and deposit invoices for renewed contract
                $this->billService->generateInitialInvoices($created);

                return $created;
            });
        } catch (\Throwable $e) {
            $this->safeDeleteUploadedFile($uploadedFilePath);
            throw $e;
        }

        if ($newContract->isActive()) {
            app(NotificationService::class)->notifyContractActivated($newContract);
        }

        return redirect()->route('owner.contracts.show', $newContract)
            ->with('success', 'Perpanjangan kontrak sewa berhasil dibuat dengan nomor '.$newContract->contract_number.'.');
    }

    /**
     * Upload a new document to the specified contract.
     */
    public function uploadDocument(StoreContractDocumentRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('uploadDocument', $contract);

        $file = $request->file('document');
        $storedPath = Storage::disk('local')->putFile('contracts', $file);

        $customTitle = $request->filled('title') ? trim((string) $request->input('title')) : null;
        $fileName = $customTitle
            ? $customTitle.'.'.$file->getClientOriginalExtension()
            : $file->getClientOriginalName();

        try {
            ContractDocument::create([
                'contract_id' => $contract->id,
                'file_name' => $fileName,
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        } catch (\Throwable $e) {
            $this->safeDeleteUploadedFile($storedPath);
            throw $e;
        }

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Dokumen kontrak berhasil diunggah.');
    }

    /**
     * Download a contract document with strict authorization.
     */
    public function downloadDocument(Contract $contract, ContractDocument $document): StreamedResponse
    {
        abort_if($document->contract_id !== $contract->id, 404, 'Dokumen tidak terkait dengan kontrak ini.');

        Gate::authorize('downloadDocument', $contract);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File dokumen tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Delete a contract document.
     */
    public function destroyDocument(Contract $contract, ContractDocument $document): RedirectResponse
    {
        abort_if($document->contract_id !== $contract->id, 404, 'Dokumen tidak terkait dengan kontrak ini.');

        // Audit Finding C-2: Documents on completed or cancelled contracts cannot be deleted
        if ($contract->isCompleted() || $contract->isCancelled()) {
            return redirect()->route('owner.contracts.show', $contract)
                ->with('error', 'Dokumen pada kontrak dengan status '.$contract->status.' sudah terkunci dan tidak dapat dihapus.');
        }

        Gate::authorize('deleteDocument', $contract);

        DB::transaction(function () use ($document) {
            $filePath = $document->file_path;
            $document->delete();

            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
        });

        return redirect()->route('owner.contracts.show', $contract)
            ->with('success', 'Dokumen kontrak berhasil dihapus.');
    }

    /**
     * Safely delete an uploaded file if database persistence fails (Audit Finding C-3).
     */
    protected function safeDeleteUploadedFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        try {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        } catch (\Throwable $cleanupException) {
            Log::error("Failed to clean up orphaned contract file at '{$path}': ".$cleanupException->getMessage());
        }
    }

    /**
     * Generate an incremental unique contract number.
     */
    protected function generateContractNumber(): string
    {
        $prefix = 'CTR-'.now()->format('Ym').'-';
        $latest = Contract::where('contract_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('contract_number');

        $sequence = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        $contractNumber = sprintf('%s%04d', $prefix, $sequence);
        while (Contract::where('contract_number', $contractNumber)->exists()) {
            $sequence++;
            $contractNumber = sprintf('%s%04d', $prefix, $sequence);
        }

        return $contractNumber;
    }
}
