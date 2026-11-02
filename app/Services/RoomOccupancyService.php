<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomOccupancyService
{
    /**
     * Synchronize the room's occupancy status based on active contracts and valid reservations.
     *
     * Rules:
     * 1. If room status is 'perbaikan' (under repair/maintenance), it MUST be preserved.
     *    Occupancy synchronization must never overwrite 'perbaikan' to 'terisi' or 'kosong'.
     * 2. If room has any active rental contract, status MUST be 'terisi'.
     * 3. If room has no active contract but has a valid unfulfilled reservation (an accepted invitation
     *    waiting for its first contract), status remains 'terisi' (reserved).
     *    Once that invitation has been converted into a contract for the same tenant and room,
     *    it is fulfilled and cannot independently keep the room occupied after the contract ends or cancels.
     * 4. If room has no active contract and no pending unfulfilled reservation, and is not in 'perbaikan',
     *    status transitions to 'kosong'.
     */
    public function syncRoomStatus(Room $room): void
    {
        // Protection for rooms under repair (Audit Finding B-3)
        if ($room->status === 'perbaikan') {
            $hasActiveContract = Contract::where('room_id', $room->id)
                ->where('status', 'aktif')
                ->exists();

            if ($hasActiveContract) {
                Log::warning("Room ID {$room->id} is marked as 'perbaikan' but has an active contract. Preserving 'perbaikan' status.");
            }

            return;
        }

        // Rule 1: Active contract takes precedence
        $hasActiveContract = Contract::where('room_id', $room->id)
            ->where('status', 'aktif')
            ->exists();

        if ($hasActiveContract) {
            if ($room->status !== 'terisi') {
                $room->update(['status' => 'terisi']);
            }

            return;
        }

        // Rule 2: Unfulfilled accepted invitation acts as an active reservation (Audit Finding B-1)
        $hasUnfulfilledReservation = Invitation::where('room_id', $room->id)
            ->whereIn('status', ['diterima', 'accepted'])
            ->where(function ($query) use ($room) {
                $query->where(function ($q) use ($room) {
                    $q->whereNotNull('user_id')
                        ->whereNotExists(function ($subquery) use ($room) {
                            $subquery->select(DB::raw(1))
                                ->from('contracts')
                                ->where('contracts.room_id', $room->id)
                                ->whereColumn('contracts.user_id', 'invitations.user_id')
                                ->whereColumn('contracts.created_at', '>=', 'invitations.created_at');
                        });
                })->orWhere(function ($q) use ($room) {
                    $q->whereNull('user_id')
                        ->whereNotExists(function ($subquery) use ($room) {
                            $subquery->select(DB::raw(1))
                                ->from('contracts')
                                ->join('users', 'users.id', '=', 'contracts.user_id')
                                ->where('contracts.room_id', $room->id)
                                ->whereColumn('users.email', 'invitations.email')
                                ->whereColumn('contracts.created_at', '>=', 'invitations.created_at');
                        });
                });
            })
            ->exists();

        if ($hasUnfulfilledReservation) {
            if ($room->status !== 'terisi') {
                $room->update(['status' => 'terisi']);
            }

            return;
        }

        // Rule 3: Vacant room
        if ($room->status !== 'kosong') {
            $room->update(['status' => 'kosong']);
        }
    }

    /**
     * Check if a room is available for the specified date range without conflicting active contracts.
     */
    public function isRoomAvailableForPeriod(
        Room $room,
        string $startDate,
        string $endDate,
        ?int $excludeContractId = null
    ): bool {
        return $this->getOverlappingContract($room, $startDate, $endDate, $excludeContractId) === null;
    }

    /**
     * Find any active contract that overlaps with the specified date range for this room.
     */
    public function getOverlappingContract(
        Room $room,
        string $startDate,
        string $endDate,
        ?int $excludeContractId = null
    ): ?Contract {
        $query = Contract::where('room_id', $room->id)
            ->where('status', 'aktif')
            ->where(function ($q) use ($startDate, $endDate) {
                // Overlap condition: existing contract start <= new end AND existing contract end >= new start
                $q->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            });

        if ($excludeContractId !== null) {
            $query->where('id', '!=', $excludeContractId);
        }

        return $query->first();
    }
}
