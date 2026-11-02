<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ComplaintService
{
    public function __construct(
        protected ?NotificationService $notificationService = null
    ) {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    /**
     * Generate an incremental globally unique complaint ticket number in the official format KMP-YYYY-XXXX.
     * Uses row locking and collision safety to prevent concurrency clashes.
     */
    public function generateTicketNumber(?CarbonInterface $date = null): string
    {
        $date = $date ? Carbon::instance($date) : now();
        $prefix = 'KMP-'.$date->format('Y').'-';

        return DB::transaction(function () use ($prefix) {
            $latest = Complaint::where('ticket_number', 'like', "{$prefix}%")
                ->orderByDesc('ticket_number')
                ->lockForUpdate()
                ->value('ticket_number');

            $sequence = 1;
            if ($latest && preg_match('/-(\d{4,})$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $candidate = sprintf('%s%04d', $prefix, $sequence);
            while (Complaint::where('ticket_number', $candidate)->exists()) {
                $sequence++;
                $candidate = sprintf('%s%04d', $prefix, $sequence);
            }

            return $candidate;
        });
    }

    /**
     * Persist a new complaint ticket filed by an authorized tenant.
     * Verifies that the tenant has an active contract for the specified room and property.
     *
     * @param  array{
     *     property_id: int,
     *     room_id: int,
     *     title: string,
     *     description: string,
     *     category?: string|ComplaintCategory|null,
     *     priority?: string|ComplaintPriority|null,
     *     photo_url?: string|null,
     * }  $data
     *
     * @throws ValidationException
     */
    public function createComplaint(array $data, User $tenant): Complaint
    {
        if (! $tenant->isTenant()) {
            throw ValidationException::withMessages([
                'user' => 'Hanya penyewa yang dapat membuat pengaduan.',
            ]);
        }

        $propertyId = (int) ($data['property_id'] ?? 0);
        $roomId = (int) ($data['room_id'] ?? 0);

        // 1. Validate property & room existence
        $property = Property::find($propertyId);
        $room = Room::find($roomId);

        if (! $property || ! $room || $room->property_id !== $property->id) {
            throw ValidationException::withMessages([
                'room_id' => 'Kamar dan properti yang dipilih tidak valid atau tidak berelasi.',
            ]);
        }

        // 2. Validate tenant occupancy: tenant must hold an active contract for this specific room and property
        $hasActiveContract = Contract::where('user_id', $tenant->id)
            ->where('property_id', $propertyId)
            ->where('room_id', $roomId)
            ->where('status', 'aktif')
            ->exists();

        if (! $hasActiveContract) {
            throw ValidationException::withMessages([
                'room_id' => 'Anda tidak memiliki kontrak sewa aktif untuk kamar atau properti ini.',
            ]);
        }

        // 3. Normalize category & priority
        $category = $data['category'] ?? ComplaintCategory::Lainnya->value;
        if ($category instanceof ComplaintCategory) {
            $category = $category->value;
        }

        $priority = $data['priority'] ?? ComplaintPriority::Sedang->value;
        if ($priority instanceof ComplaintPriority) {
            $priority = $priority->value;
        }

        $photoUrl = isset($data['photo_url']) && is_string($data['photo_url']) ? trim($data['photo_url']) : null;

        $complaint = DB::transaction(function () use ($data, $tenant, $propertyId, $roomId, $category, $priority, $photoUrl) {
            $ticketNumber = $this->generateTicketNumber();

            return Complaint::create([
                'ticket_number' => $ticketNumber,
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'user_id' => $tenant->id,
                'title' => trim((string) $data['title']),
                'description' => trim((string) $data['description']),
                'category' => $category,
                'priority' => $priority,
                'status' => ComplaintStatus::Baru->value,
                'photo_url' => $photoUrl,
                'handled_by' => null,
                'estimated_completion' => null,
                'resolved_at' => null,
            ]);
        });

        // Safely notify property owner of new complaint submission
        $this->notificationService->notifyComplaintCreated($complaint->load(['property.owner', 'room', 'user']));

        return $complaint;
    }

    /**
     * Add a conversational reply to an active complaint ticket.
     * Preserves chronological message history and updates lifecycle status if appropriate.
     *
     * @throws ValidationException
     */
    public function addReply(Complaint $complaint, User $author, string $message, ?string $attachmentUrl = null): ComplaintReply
    {
        $trimmedMessage = trim($message);
        if ($trimmedMessage === '') {
            throw ValidationException::withMessages([
                'message' => 'Pesan balasan tidak boleh kosong.',
            ]);
        }

        $reply = DB::transaction(function () use ($complaint, $author, $trimmedMessage, $attachmentUrl) {
            /** @var Complaint $lockedComplaint */
            $lockedComplaint = Complaint::where('id', $complaint->id)->lockForUpdate()->firstOrFail();

            // Guard: closed tickets cannot receive new replies
            if ($lockedComplaint->isClosed()) {
                throw ValidationException::withMessages([
                    'message' => 'Tiket pengaduan ini telah ditutup permanen dan tidak dapat menerima balasan baru.',
                ]);
            }

            // Guard: author must be authorized participant (the tenant who filed it, or the owner of the property)
            if (! $this->canUserParticipateInTicket($author, $lockedComplaint)) {
                throw ValidationException::withMessages([
                    'user' => 'Anda tidak memiliki hak akses untuk membalas tiket pengaduan ini.',
                ]);
            }

            $reply = ComplaintReply::create([
                'complaint_id' => $lockedComplaint->id,
                'user_id' => $author->id,
                'message' => $trimmedMessage,
                'attachment_url' => $attachmentUrl,
            ]);

            // If tenant replies while ticket was waiting for response, transition back to in progress
            if ($author->isTenant() && $lockedComplaint->status === ComplaintStatus::MenungguTanggapan->value) {
                $lockedComplaint->update([
                    'status' => ComplaintStatus::Diproses->value,
                ]);
            }

            $lockedComplaint->touch();

            return $reply->load('user');
        });

        // Safely notify counter-party of new reply
        $this->notificationService->notifyComplaintReplied($complaint->load(['property.owner', 'user']), $reply, $author);

        return $reply;
    }

    /**
     * Transition a complaint ticket through its lifecycle (Baru -> Diproses -> MenungguTanggapan -> Selesai -> Ditutup).
     *
     * @throws ValidationException
     */
    public function updateStatus(
        Complaint $complaint,
        ComplaintStatus|string $status,
        User $actor,
        ?string $handledBy = null,
        ?CarbonInterface $estimatedCompletion = null
    ): Complaint {
        $targetStatus = $status instanceof ComplaintStatus ? $status : ComplaintStatus::tryFrom((string) $status);

        if (! $targetStatus) {
            throw new InvalidArgumentException("Status pengaduan tidak valid: '{$status}'.");
        }

        return DB::transaction(function () use ($complaint, $targetStatus, $actor, $handledBy, $estimatedCompletion) {
            /** @var Complaint $lockedComplaint */
            $lockedComplaint = Complaint::where('id', $complaint->id)->lockForUpdate()->firstOrFail();

            // Authorization: Owner of the property or Tenant who filed the complaint
            $isOwner = (bool) $lockedComplaint->property?->isOwnedBy($actor);
            $isTenant = $lockedComplaint->user_id === $actor->id;

            if (! $isOwner && ! $isTenant) {
                throw ValidationException::withMessages([
                    'status' => 'Anda tidak memiliki hak akses untuk memperbarui status pengaduan ini.',
                ]);
            }

            // Tenant authorization rule: Tenants can only close a resolved ticket (confirm completion)
            if ($isTenant && ! $isOwner && ($targetStatus !== ComplaintStatus::Ditutup || ! $lockedComplaint->isResolved())) {
                throw ValidationException::withMessages([
                    'status' => 'Penyewa hanya dapat menutup tiket pengaduan yang telah diselesaikan.',
                ]);
            }

            // Once permanently closed, a ticket cannot be reopened except by the property owner
            if ($lockedComplaint->isClosed() && $targetStatus !== ComplaintStatus::Ditutup && ! $isOwner) {
                throw ValidationException::withMessages([
                    'status' => 'Tiket yang telah ditutup tidak dapat dibuka kembali oleh penyewa.',
                ]);
            }

            // A ticket cannot be reverted back to 'Baru' once it has moved to another status
            if ($targetStatus === ComplaintStatus::Baru && $lockedComplaint->status !== ComplaintStatus::Baru->value) {
                throw ValidationException::withMessages([
                    'status' => 'Tiket yang telah diproses tidak dapat dikembalikan ke status Baru.',
                ]);
            }

            $previousStatus = (string) $lockedComplaint->status;

            $updateData = [
                'status' => $targetStatus->value,
            ];

            if ($isOwner && $handledBy !== null) {
                $updateData['handled_by'] = trim($handledBy);
            }

            if ($isOwner && $estimatedCompletion !== null) {
                $updateData['estimated_completion'] = Carbon::instance($estimatedCompletion);
            }

            // Handle resolution timestamp
            if ($targetStatus === ComplaintStatus::Selesai && ! $lockedComplaint->resolved_at) {
                $updateData['resolved_at'] = now();
            } elseif ($targetStatus === ComplaintStatus::Diproses && $lockedComplaint->resolved_at) {
                // If reopened for further handling, reset resolved_at
                $updateData['resolved_at'] = null;
            }

            // Handle closure timestamp
            if ($targetStatus === ComplaintStatus::Ditutup && ! $lockedComplaint->closed_at) {
                $updateData['closed_at'] = now();
            } elseif ($targetStatus !== ComplaintStatus::Ditutup && $lockedComplaint->closed_at) {
                // If reopened by owner, reset closed_at
                $updateData['closed_at'] = null;
            }

            $lockedComplaint->update($updateData);

            $fresh = $lockedComplaint->fresh(['property', 'room', 'user', 'replies']);

            // Safely notify tenant when owner updates status
            if ($isOwner && $fresh->user && $fresh->status !== $previousStatus) {
                $this->notificationService->notifyComplaintStatusUpdated($fresh, $previousStatus, $fresh->status);
            }

            return $fresh;
        });
    }

    /**
     * Auto-close tickets that have been marked as resolved ('selesai') for more than threshold days (default H+7).
     *
     * @param  int  $daysThreshold  Number of days after resolution (default 7)
     * @param  CarbonInterface|null  $asOf  Reference date/time (defaults to now)
     * @return int Number of tickets automatically closed
     */
    public function autoCloseResolvedComplaints(int $daysThreshold = 7, ?CarbonInterface $asOf = null): int
    {
        $referenceTime = $asOf ? Carbon::instance($asOf) : now();
        $cutoff = $referenceTime->copy()->subDays($daysThreshold);

        return Complaint::where('status', ComplaintStatus::Selesai->value)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->update([
                'status' => ComplaintStatus::Ditutup->value,
                'closed_at' => $referenceTime,
            ]);
    }

    /**
     * Check if a user can participate (view and reply) in a complaint ticket.
     */
    public function canUserParticipateInTicket(User $user, Complaint $complaint): bool
    {
        if ($user->isOwner()) {
            return (bool) $complaint->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $complaint->user_id === $user->id;
        }

        return false;
    }
}
