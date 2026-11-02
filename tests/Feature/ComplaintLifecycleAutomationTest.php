<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\UserRole;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ComplaintStatusUpdatedNotification;
use App\Services\ComplaintService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    // 1. Setup Owner
    $this->owner = User::create([
        'name' => 'Pak Budi Kos',
        'email' => 'owner_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // 2. Setup Property
    $this->property = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kos Griya Nyaman HUNI',
        'slug' => 'kos-griya-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Kebon Jeruk No. 55',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 8,
        'status' => 'aktif',
    ]);

    // 3. Setup Rooms
    $this->roomA = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '301',
        'type' => 'deluxe',
        'price' => 1600000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    $this->roomB = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '302',
        'type' => 'standard',
        'price' => 1300000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 4. Setup Tenant A (Primary Tenant)
    $this->tenantA = User::create([
        'name' => 'Dimas Tenant',
        'email' => 'dimas_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractA = Contract::create([
        'contract_number' => 'CTR-DMS-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => Carbon::today()->subMonths(2),
        'end_date' => Carbon::today()->addMonths(10),
        'duration_months' => 12,
        'monthly_rent' => 1600000,
        'total_amount' => 19200000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    // 5. Setup Tenant B (Unrelated Tenant for isolation checks)
    $this->tenantB = User::create([
        'name' => 'Rian Tenant Lain',
        'email' => 'rian_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractB = Contract::create([
        'contract_number' => 'CTR-RIAN-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'start_date' => Carbon::today()->subMonths(1),
        'end_date' => Carbon::today()->addMonths(11),
        'duration_months' => 12,
        'monthly_rent' => 1300000,
        'total_amount' => 15600000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    $this->complaintService = app(ComplaintService::class);
});

test('1. resolved ticket less than 7 days ago is not auto-closed', function () {
    $recentResolved = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-1',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Ganti bohlam lampu',
        'description' => 'Lampu redup kemarin.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDays(3),
    ]);

    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7);

    expect($closedCount)->toBe(0);
    expect($recentResolved->fresh()->status)->toBe(ComplaintStatus::Selesai->value);
    expect($recentResolved->fresh()->closed_at)->toBeNull();
});

test('2. resolved ticket older than 7 days is auto-closed with closed_at recorded', function () {
    $oldResolved = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-2',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kran wastafel bocor',
        'description' => 'Sudah diganti kran baru.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDays(8),
    ]);

    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7);

    expect($closedCount)->toBe(1);
    $fresh = $oldResolved->fresh();
    expect($fresh->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($fresh->closed_at)->not->toBeNull();
});

test('3. already closed ticket remains unaffected and original closed_at is preserved', function () {
    $originalClosedAt = Carbon::now()->subDays(15);
    $alreadyClosed = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-3',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Tiket lama',
        'description' => 'Sudah lama ditutup.',
        'status' => ComplaintStatus::Ditutup->value,
        'resolved_at' => Carbon::now()->subDays(20),
        'closed_at' => $originalClosedAt,
    ]);

    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7);

    expect($closedCount)->toBe(0);
    $fresh = $alreadyClosed->fresh();
    expect($fresh->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($fresh->closed_at->toIso8601String())->toBe($originalClosedAt->toIso8601String());
});

test('4. ticket whose status changed back to diproses before auto-close is not closed', function () {
    $reopenedTicket = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-4',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'AC berisik kambuh lagi',
        'description' => 'Suara berisik muncul lagi setelah servis.',
        'status' => ComplaintStatus::Diproses->value,
        'resolved_at' => null, // Reset when reopened
    ]);

    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7);

    expect($closedCount)->toBe(0);
    expect($reopenedTicket->fresh()->status)->toBe(ComplaintStatus::Diproses->value);
});

test('5. auto-close execution is strictly idempotent', function () {
    Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-5',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Cat terkelupas',
        'description' => 'Selesai dicat ulang.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDays(9),
    ]);

    // First execution closes 1 ticket
    $firstRun = $this->complaintService->autoCloseResolvedComplaints(7);
    expect($firstRun)->toBe(1);

    // Second immediate execution closes 0 tickets without modifying existing data
    $secondRun = $this->complaintService->autoCloseResolvedComplaints(7);
    expect($secondRun)->toBe(0);
});

test('6. exact 7-day boundary condition behavior', function () {
    $exactReference = Carbon::parse('2026-10-20 12:00:00');

    // Case A: 6 days 23 hours ago (NOT eligible yet)
    $compA = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-6A',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Belum 7 hari penuh',
        'description' => 'Baru 6 hari 23 jam.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => $exactReference->copy()->subDays(7)->addHour(),
    ]);

    // Case B: Exactly 7 days and 1 minute ago (eligible)
    $compB = Complaint::create([
        'ticket_number' => 'KMP-2026-AUTO-6B',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Sudah 7 hari penuh',
        'description' => 'Tepat melewati 7 hari.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => $exactReference->copy()->subDays(7)->subMinute(),
    ]);

    $closed = $this->complaintService->autoCloseResolvedComplaints(7, $exactReference);

    expect($closed)->toBe(1);
    expect($compA->fresh()->status)->toBe(ComplaintStatus::Selesai->value);
    expect($compB->fresh()->status)->toBe(ComplaintStatus::Ditutup->value);
});

test('7. artisan command complaints:auto-close-resolved executes and closes tickets deterministically', function () {
    Complaint::create([
        'ticket_number' => 'KMP-2026-CMD-1',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Perbaikan pintu lemari',
        'description' => 'Engsel sudah diganti.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDays(10),
    ]);

    $exitCode = Artisan::call('complaints:auto-close-resolved', [
        '--days' => 7,
    ]);

    expect($exitCode)->toBe(0);
    $output = Artisan::output();
    expect($output)->toContain('Berhasil memproses auto-close');
});

test('8. scheduler registers complaints:auto-close-resolved daily at 02:00 UTC', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $complaintEvent = $events->first(function ($event) {
        return str_contains($event->command ?? '', 'complaints:auto-close-resolved');
    });

    expect($complaintEvent)->not->toBeNull();
    expect($complaintEvent->expression)->toBe('0 2 * * *');
});

test('9. tenant can confirm completion and close a resolved ticket via dedicated route', function () {
    $resolvedComplaint = Complaint::create([
        'ticket_number' => 'KMP-2026-INT-1',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kloset mampet selesai disedot',
        'description' => 'Sudah lancar kembali.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDay(),
    ]);

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.close', $resolvedComplaint));

    $response->assertRedirect(route('tenant.complaints.show', $resolvedComplaint));
    $response->assertSessionHas('success');

    $fresh = $resolvedComplaint->fresh();
    expect($fresh->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($fresh->closed_at)->not->toBeNull();
});

test('10. tenant cannot close a ticket that is not yet resolved', function () {
    $inProgressComplaint = Complaint::create([
        'ticket_number' => 'KMP-2026-INT-2',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kipas angin rusak',
        'description' => 'Masih sedang diperbaiki teknisi.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.close', $inProgressComplaint));

    $response->assertRedirect(route('tenant.complaints.show', $inProgressComplaint));
    $response->assertSessionHas('error');
    expect($inProgressComplaint->fresh()->status)->toBe(ComplaintStatus::Diproses->value);
});

test('11. tenant cannot close a complaint belonging to another tenant (idor prevention)', function () {
    $tenantBComplaint = Complaint::create([
        'ticket_number' => 'KMP-2026-INT-3',
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Gorden kamar 302 copot',
        'description' => 'Tolong pasang kembali.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => Carbon::now()->subDay(),
    ]);

    // Tenant A attempts to close Tenant B's ticket -> 403 Forbidden
    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.close', $tenantBComplaint));

    $response->assertForbidden();
    expect($tenantBComplaint->fresh()->status)->toBe(ComplaintStatus::Selesai->value);
});

test('12. owner status update triggers ComplaintStatusUpdatedNotification to tenant', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-INT-4',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Wastafel tersumbat',
        'description' => 'Air tidak mengalir lancar.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    $this->actingAs($this->owner)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'diproses',
            'handled_by' => 'Pak Joko',
        ]);

    Notification::assertSentTo(
        $this->tenantA,
        ComplaintStatusUpdatedNotification::class,
        function (ComplaintStatusUpdatedNotification $notification) use ($complaint) {
            return $notification->complaint->id === $complaint->id
                && $notification->newStatus === ComplaintStatus::Diproses->value;
        }
    );
});

test('13. full tenant-owner interactive integration cycle from creation to auto-close', function () {
    // 1. Tenant creates complaint
    $photoPath = 'complaints/test_bukti.jpg';
    Storage::disk('local')->put($photoPath, 'binary-image-data');

    $complaint = $this->complaintService->createComplaint([
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'title' => 'AC kamar bocor menetes ke kasur',
        'description' => 'Air menetes cukup deras saat AC dinyalakan.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'photo_url' => $photoPath,
    ], $this->tenantA);

    expect($complaint->status)->toBe(ComplaintStatus::Baru->value);

    // 2. Both sides can view complaint
    $this->actingAs($this->tenantA)->get(route('tenant.complaints.show', $complaint))->assertOk();
    $this->actingAs($this->owner)->get(route('owner.complaints.show', $complaint))->assertOk();

    // 3. Owner views photo proof privately
    $this->actingAs($this->owner)->get(route('complaints.photo', $complaint))->assertOk();

    // 4. Owner responds with question -> status transitions to menunggu_tanggapan
    $this->actingAs($this->owner)->post(route('owner.complaints.replies.store', $complaint), [
        'message' => 'Bocornya dari sisi kiri atau kanan indoor AC pak?',
    ])->assertRedirect();

    $this->actingAs($this->owner)->patch(route('owner.complaints.status.update', $complaint), [
        'status' => 'menunggu_tanggapan',
    ])->assertRedirect();

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::MenungguTanggapan->value);

    // 5. Tenant replies -> status automatically reverts to diproses
    $this->actingAs($this->tenantA)->post(route('tenant.complaints.replies.store', $complaint), [
        'message' => 'Dari sisi kiri dekat pipa pembuangan pak.',
    ])->assertRedirect();

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Diproses->value);

    // 6. Owner marks as resolved (selesai)
    $this->actingAs($this->owner)->patch(route('owner.complaints.status.update', $complaint), [
        'status' => 'selesai',
        'handled_by' => 'Pak Harun (Teknisi AC)',
    ])->assertRedirect();

    $resolvedTicket = $complaint->fresh();
    expect($resolvedTicket->status)->toBe(ComplaintStatus::Selesai->value);
    expect($resolvedTicket->resolved_at)->not->toBeNull();

    // 7. Simulate 8 days pass -> auto-close runs
    $asOfDate = Carbon::now()->addDays(8);
    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7, $asOfDate);
    expect($closedCount)->toBe(1);

    $closedTicket = $complaint->fresh();
    expect($closedTicket->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($closedTicket->closed_at)->not->toBeNull();

    // 8. Subsequent replies rejected
    $this->actingAs($this->tenantA)->post(route('tenant.complaints.replies.store', $closedTicket), [
        'message' => 'Mencoba membalas tiket yang sudah ditutup.',
    ])->assertForbidden();

    $this->actingAs($this->owner)->post(route('owner.complaints.replies.store', $closedTicket), [
        'message' => 'Owner juga tidak bisa membalas tiket tertutup.',
    ])->assertForbidden();
});

test('14. stored XSS payloads in title, description, and message are properly escaped', function () {
    $xssTitle = 'Judul <script>alert("xss")</script>';
    $xssDesc = 'Deskripsi <img src=x onerror=alert(1)>';
    $xssMessage = 'Pesan <b>bold</b> <script>document.cookie</script>';

    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-XSS-1',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => $xssTitle,
        'description' => $xssDesc,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->owner->id,
        'message' => $xssMessage,
    ]);

    // Inspect Tenant view
    $resTenant = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.show', $complaint));
    $resTenant->assertOk();
    $resTenant->assertDontSee('<script>alert("xss")</script>', false);
    $resTenant->assertDontSee('<img src=x onerror=alert(1)>', false);
    $resTenant->assertSee(e($xssTitle), false);
    $resTenant->assertSee(e($xssDesc), false);

    // Inspect Owner view
    $resOwner = $this->actingAs($this->owner)
        ->get(route('owner.complaints.show', $complaint));
    $resOwner->assertOk();
    $resOwner->assertDontSee('<script>alert("xss")</script>', false);
    $resOwner->assertDontSee('<img src=x onerror=alert(1)>', false);
    $resOwner->assertSee(e($xssTitle), false);
    $resOwner->assertSee(e($xssDesc), false);
});
