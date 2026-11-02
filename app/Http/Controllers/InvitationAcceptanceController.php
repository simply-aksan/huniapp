<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomOccupancyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationAcceptanceController extends Controller
{
    public function __construct(
        protected RoomOccupancyService $occupancyService
    ) {}

    /**
     * Show the public invitation landing page for the given token.
     */
    public function show(string $token): View
    {
        $invitation = Invitation::where('token', $token)
            ->with(['room.property', 'sender', 'tenant'])
            ->firstOrFail();

        $currentUser = Auth::user();
        $isEmailMatching = false;
        $userExistsForEmail = false;

        if ($currentUser) {
            $isEmailMatching = strcasecmp($currentUser->email, $invitation->email ?? '') === 0;
        } else {
            $userExistsForEmail = User::where('email', $invitation->email)->exists();
        }

        return view('invitations.show', [
            'invitation' => $invitation,
            'property' => $invitation->room->property,
            'room' => $invitation->room,
            'currentUser' => $currentUser,
            'isEmailMatching' => $isEmailMatching,
            'userExistsForEmail' => $userExistsForEmail,
        ]);
    }

    /**
     * Accept the invitation by an authenticated tenant user whose email matches.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login', ['redirect' => route('invitations.show', $token)])
                ->with('error', 'Silakan login terlebih dahulu untuk menerima undangan sewa.');
        }

        if (! $user->isTenant()) {
            return redirect()->back()
                ->with('error', 'Akun Pemilik tidak dapat menerima undangan sewa penyewa. Silakan login menggunakan akun Penyewa.');
        }

        return DB::transaction(function () use ($token, $user) {
            /** @var Invitation $invitation */
            $invitation = Invitation::where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify invitation status
            if ($invitation->isExpired()) {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Undangan ini telah kedaluwarsa dan tidak dapat diterima.');
            }

            if ($invitation->isRevoked()) {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Undangan ini telah dibatalkan oleh pemilik kos.');
            }

            if ($invitation->isAccepted()) {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Undangan ini sudah pernah diterima sebelumnya.');
            }

            // 2. Verify email identity match
            if (strcasecmp($user->email, $invitation->email ?? '') !== 0) {
                return redirect()->route('invitations.show', $token)
                    ->with('error', "Email akun Anda ({$user->email}) tidak sesuai dengan email penerima undangan ({$invitation->email}).");
            }

            // 3. Verify room availability and acquire pessimistic lock on the room
            /** @var Room $room */
            $room = Room::where('id', $invitation->room_id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasActiveContract = Contract::where('room_id', $room->id)
                ->where('status', 'aktif')
                ->exists();

            if ($hasActiveContract || $room->status === 'terisi') {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Maaf, kamar ini sedang ditempati dan tidak dapat disewa saat ini.');
            }

            // 4. Mark invitation as accepted and link tenant account
            $invitation->update([
                'status' => 'diterima',
                'accepted_at' => now(),
                'user_id' => $user->id,
            ]);

            // Synchronize room occupancy status
            $this->occupancyService->syncRoomStatus($room);

            return redirect()->route('tenant.dashboard')
                ->with('success', "Selamat! Anda telah berhasil menerima undangan sewa untuk Kamar {$room->room_number} di {$room->property->name}.");
        });
    }

    /**
     * Register a new tenant account and accept the invitation simultaneously.
     */
    public function registerAndAccept(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'occupation' => ['nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($token, $validated, $request) {
            /** @var Invitation $invitation */
            $invitation = Invitation::where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isPending()) {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Undangan sudah tidak berlaku atau telah diproses sebelumnya.');
            }

            // Verify room availability and acquire pessimistic lock on the room
            /** @var Room $room */
            $room = Room::where('id', $invitation->room_id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasActiveContract = Contract::where('room_id', $room->id)
                ->where('status', 'aktif')
                ->exists();

            if ($hasActiveContract || $room->status === 'terisi') {
                return redirect()->route('invitations.show', $token)
                    ->with('error', 'Maaf, kamar ini sedang ditempati dan tidak dapat disewa saat ini.');
            }

            $email = strtolower(trim($invitation->email));

            // Check if user already exists
            if (User::where('email', $email)->exists()) {
                return redirect()->route('login', ['redirect' => route('invitations.show', $token)])
                    ->with('error', 'Akun dengan email ini sudah terdaftar. Silakan login untuk menerima undangan.');
            }

            // Create new tenant user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? $invitation->phone,
                'nik' => $validated['nik'] ?? null,
                'occupation' => $validated['occupation'] ?? 'Penyewa Kos',
                'role' => UserRole::Tenant,
            ]);

            // Authenticate user
            Auth::login($user);
            $request->session()->regenerate();

            // Link invitation to this new user
            $invitation->update([
                'status' => 'diterima',
                'accepted_at' => now(),
                'user_id' => $user->id,
            ]);

            // Synchronize room occupancy status
            $this->occupancyService->syncRoomStatus($room);

            return redirect()->route('tenant.dashboard')
                ->with('success', "Selamat datang di HUNI! Anda telah berhasil bergabung sebagai penyewa di Kamar {$room->room_number}.");
        });
    }
}
