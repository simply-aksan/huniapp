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
use App\Services\ComplaintService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->complaintService = app(ComplaintService::class);

    // Setup Owner A & Property A & Room A1
    $this->ownerA = User::create([
        'name' => 'Owner Alpha',
        'email' => 'owner_alpha_'.Str::random(8).'@huniapp.test',
        'phone' => '0811'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyA = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Alpha Harmoni',
        'slug' => 'kos-alpha-harmoni-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Alpha No. 1',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '101',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'terisi',
    ]);

    $this->roomA2 = Room::create([
        'property_id' => $this->propertyA->id,
        'room_number' => '102',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'kosong',
    ]);

    // Setup Owner B & Property B (Unrelated Owner)
    $this->ownerB = User::create([
        'name' => 'Owner Beta',
        'email' => 'owner_beta_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Beta Harmoni',
        'slug' => 'kos-beta-harmoni-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Beta No. 2',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->roomB1 = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => '201',
        'floor' => 1,
        'type' => 'deluxe',
        'price' => 2500000,
        'status' => 'terisi',
    ]);

    // Setup Tenant A (Leases Room A1)
    $this->tenantA = User::create([
        'name' => 'Tenant Budi',
        'email' => 'tenant_budi_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractA = Contract::create([
        'contract_number' => 'CTR-ALPHA-'.Str::random(5),
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'status' => 'aktif',
    ]);

    // Setup Tenant B (Unrelated Tenant)
    $this->tenantB = User::create([
        'name' => 'Tenant Citra',
        'email' => 'tenant_citra_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

test('1. complaint and complaint replies relationships load correctly and preserve chronological order', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0001',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'AC tidak dingin',
        'description' => 'AC di kamar 101 hanya mengeluarkan angin panas.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    expect($complaint->property->id)->toBe($this->propertyA->id);
    expect($complaint->room->id)->toBe($this->roomA1->id);
    expect($complaint->user->id)->toBe($this->tenantA->id);

    // Create replies
    $reply1 = ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->ownerA->id,
        'message' => 'Baik, teknisi AC kami akan datang jam 14:00.',
    ]);

    $reply2 = ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->tenantA->id,
        'message' => 'Terima kasih pak kos, saya tunggu.',
    ]);

    $freshComplaint = $complaint->fresh(['replies.user']);
    expect($freshComplaint->replies)->toHaveCount(2);
    expect($freshComplaint->replies->first()->id)->toBe($reply1->id);
    expect($freshComplaint->replies->last()->id)->toBe($reply2->id);
    expect($reply1->isFromOwner())->toBeTrue();
    expect($reply2->isFromTenant())->toBeTrue();
});

test('2. complaints service generates incremental globally unique ticket numbers', function () {
    $ticket1 = $this->complaintService->generateTicketNumber();
    expect($ticket1)->toMatch('/^KMP-\d{4}-\d{4}$/');

    // When a complaint is created with this ticket
    $this->complaintService->createComplaint([
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'title' => 'Tiket pertama',
        'description' => 'Deskripsi tiket pertama.',
    ], $this->tenantA);

    $ticket2 = $this->complaintService->generateTicketNumber();
    expect($ticket2)->toMatch('/^KMP-\d{4}-\d{4}$/');
    expect($ticket1)->not->toBe($ticket2);
});

test('3. tenant can only view and access their own complaints (idor prevention)', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0002',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kran air bocor',
        'description' => 'Kran wastafel bocor menetes terus.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Tenant A (owner of the ticket) is allowed
    expect(Gate::forUser($this->tenantA)->allows('view', $complaint))->toBeTrue();

    // Tenant B (unrelated tenant) is strictly denied
    expect(Gate::forUser($this->tenantB)->allows('view', $complaint))->toBeFalse();

    // Query scope for tenant A includes complaint, tenant B excludes it
    expect(Complaint::forTenant($this->tenantA)->pluck('id'))->toContain($complaint->id);
    expect(Complaint::forTenant($this->tenantB)->pluck('id'))->not->toContain($complaint->id);
});

test('4. owner can only view complaints for properties they own (owner isolation)', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0003',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Lampu balkon mati',
        'description' => 'Lampu balkon depan kamar mati.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Owner A (owns Property A) is allowed
    expect(Gate::forUser($this->ownerA)->allows('view', $complaint))->toBeTrue();

    // Owner B (unrelated owner) is strictly denied
    expect(Gate::forUser($this->ownerB)->allows('view', $complaint))->toBeFalse();

    // Query scope for owner A includes complaint, owner B excludes it
    expect(Complaint::forOwner($this->ownerA)->pluck('id'))->toContain($complaint->id);
    expect(Complaint::forOwner($this->ownerB)->pluck('id'))->not->toContain($complaint->id);
});

test('5. tenant cannot file a complaint for a room or property they do not actively lease', function () {
    // Tenant A attempts to file complaint for Room A2 (empty room, not leased by Tenant A)
    expect(function () {
        $this->complaintService->createComplaint([
            'property_id' => $this->propertyA->id,
            'room_id' => $this->roomA2->id,
            'title' => 'Pintu rusak',
            'description' => 'Pintu kamar 102 engselnya lepas.',
        ], $this->tenantA);
    })->toThrow(ValidationException::class);

    // Tenant A attempts to file complaint for Room B1 on Property B (Property B belongs to Owner B)
    expect(function () {
        $this->complaintService->createComplaint([
            'property_id' => $this->propertyB->id,
            'room_id' => $this->roomB1->id,
            'title' => 'Pintu rusak di kos lain',
            'description' => 'Pintu kamar 201 kos beta rusak.',
        ], $this->tenantA);
    })->toThrow(ValidationException::class);
});

test('6. tenant with active contract successfully files a complaint with default baru status', function () {
    $complaint = $this->complaintService->createComplaint([
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'title' => 'Stop kontak kamar mandi tidak berfungsi',
        'description' => 'Stop kontak dekat wastafel tidak ada arus listrik.',
        'category' => ComplaintCategory::Listrik,
        'priority' => ComplaintPriority::Tinggi,
    ], $this->tenantA);

    expect($complaint)->not->toBeNull();
    expect($complaint->ticket_number)->toMatch('/^KMP-\d{4}-\d{4}$/');
    expect($complaint->status)->toBe(ComplaintStatus::Baru->value);
    expect($complaint->isOpen())->toBeTrue();
    expect($complaint->user_id)->toBe($this->tenantA->id);
    expect($complaint->property_id)->toBe($this->propertyA->id);
    expect($complaint->room_id)->toBe($this->roomA1->id);
});

test('7. unauthorized users cannot reply to a complaint ticket', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0004',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kunci kamar macet',
        'description' => 'Kunci silinder agak macet.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    // Unrelated Tenant B attempts to reply
    expect(function () use ($complaint) {
        $this->complaintService->addReply($complaint, $this->tenantB, 'Saya ikut menimpali.');
    })->toThrow(ValidationException::class);

    // Unrelated Owner B attempts to reply
    expect(function () use ($complaint) {
        $this->complaintService->addReply($complaint, $this->ownerB, 'Saya coba jawab.');
    })->toThrow(ValidationException::class);

    expect($complaint->replies()->count())->toBe(0);
});

test('8. closed tickets cannot receive new replies (immutability)', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0005',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Wifi mati',
        'description' => 'Wifi lantai 1 tidak ada sinyal.',
        'status' => ComplaintStatus::Ditutup->value,
    ]);

    expect($complaint->isClosed())->toBeTrue();
    expect($complaint->canBeReplied())->toBeFalse();

    expect(function () use ($complaint) {
        $this->complaintService->addReply($complaint, $this->tenantA, 'Coba balas tiket yang sudah ditutup');
    })->toThrow(ValidationException::class);

    expect(Gate::forUser($this->tenantA)->allows('reply', $complaint))->toBeFalse();
    expect(Gate::forUser($this->ownerA)->allows('reply', $complaint))->toBeFalse();
});

test('9. lifecycle status transitions: baru -> diproses -> menunggu_tanggapan -> selesai -> ditutup', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0006',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Plafon bocor saat hujan deras',
        'description' => 'Air menetes dari sudut plafon kamar.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    // 1. Owner updates to 'diproses' with technician and estimated time
    $est = now()->addDays(2);
    $inProgress = $this->complaintService->updateStatus(
        $complaint,
        ComplaintStatus::Diproses,
        $this->ownerA,
        'Bambang (Tukang Atap)',
        $est
    );

    expect($inProgress->status)->toBe(ComplaintStatus::Diproses->value);
    expect($inProgress->handled_by)->toBe('Bambang (Tukang Atap)');
    expect($inProgress->estimated_completion)->not->toBeNull();
    expect($inProgress->resolved_at)->toBeNull();

    // 2. Owner asks tenant clarification -> 'menunggu_tanggapan'
    $waiting = $this->complaintService->updateStatus($inProgress, ComplaintStatus::MenungguTanggapan, $this->ownerA);
    expect($waiting->status)->toBe(ComplaintStatus::MenungguTanggapan->value);
    expect($waiting->isPendingResponse())->toBeTrue();

    // 3. Tenant replies -> status automatically transitions back to 'diproses'
    $this->complaintService->addReply($waiting, $this->tenantA, 'Bocornya persis di atas lemari pakaian pak.');
    expect($waiting->fresh()->status)->toBe(ComplaintStatus::Diproses->value);

    // 4. Owner marks as resolved -> 'selesai' sets resolved_at timestamp
    $resolved = $this->complaintService->updateStatus($waiting, ComplaintStatus::Selesai, $this->ownerA);
    expect($resolved->status)->toBe(ComplaintStatus::Selesai->value);
    expect($resolved->isResolved())->toBeTrue();
    expect($resolved->resolved_at)->not->toBeNull();

    // 5. Tenant confirms completion and closes -> 'ditutup'
    $closed = $this->complaintService->updateStatus($resolved, ComplaintStatus::Ditutup, $this->tenantA);
    expect($closed->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($closed->isClosed())->toBeTrue();
});

test('10. tenant cannot illegally manipulate complaint status to in_progress or resolved', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0007',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Keran wastafel patah',
        'description' => 'Keran patah saat diputar.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Tenant cannot self-transition to 'diproses' or 'selesai'
    expect(function () use ($complaint) {
        $this->complaintService->updateStatus($complaint, ComplaintStatus::Diproses, $this->tenantA);
    })->toThrow(ValidationException::class);

    expect(function () use ($complaint) {
        $this->complaintService->updateStatus($complaint, ComplaintStatus::Selesai, $this->tenantA);
    })->toThrow(ValidationException::class);
});

test('11. auto-close H+7 business logic identifies and closes tickets resolved > 7 days ago', function () {
    // Complaint resolved 10 days ago (eligible)
    $oldResolved = Complaint::create([
        'ticket_number' => 'KMP-TEST-0008',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Ganti lampu kamar mandi',
        'description' => 'Lampu redup.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now()->subDays(10),
    ]);

    // Complaint resolved 2 days ago (not eligible)
    $recentResolved = Complaint::create([
        'ticket_number' => 'KMP-TEST-0009',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Ganti kran cuci piring',
        'description' => 'Kran agak longgar.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now()->subDays(2),
    ]);

    expect($oldResolved->isEligibleForAutoClose())->toBeTrue();
    expect($recentResolved->isEligibleForAutoClose())->toBeFalse();

    $closedCount = $this->complaintService->autoCloseResolvedComplaints(7);
    expect($closedCount)->toBeGreaterThanOrEqual(1);

    expect($oldResolved->fresh()->status)->toBe(ComplaintStatus::Ditutup->value);
    expect($recentResolved->fresh()->status)->toBe(ComplaintStatus::Selesai->value);
});

test('12. foreign key and operational deletion protection prevents accidental data loss', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0010',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Sampah di lorong menumpuk',
        'description' => 'Perlu dibersihkan segera.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->ownerA->id,
        'message' => 'Petugas kebersihan sedang jalan ke sana.',
    ]);

    // Operational policy denies hard-deletion of complaints that have replies
    expect(Gate::forUser($this->ownerA)->allows('delete', $complaint))->toBeFalse();

    // User restrictOnDelete prevents deleting tenant who owns complaints
    expect(function () {
        $this->tenantA->delete();
    })->toThrow(QueryException::class);
});

test('13. enum values and badge variants format consistently', function () {
    expect(ComplaintStatus::values())->toContain('baru', 'diproses', 'menunggu_tanggapan', 'selesai', 'ditutup');
    expect(ComplaintPriority::values())->toContain('rendah', 'sedang', 'tinggi', 'darurat');
    expect(ComplaintCategory::values())->toContain('Listrik', 'Air & Sanitasi', 'Fasilitas Kamar', 'Lainnya');

    $complaint = Complaint::create([
        'ticket_number' => 'KMP-TEST-0011',
        'property_id' => $this->propertyA->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kabel terkelupas di dekat colokan',
        'description' => 'Bahaya korsleting.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Darurat->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    expect($complaint->statusBadgeVariant())->toBe('diproses');
    expect($complaint->priorityBadgeVariant())->toBe('danger');
    expect($complaint->statusEnum())->toBe(ComplaintStatus::Diproses);
    expect($complaint->priorityEnum())->toBe(ComplaintPriority::Darurat);
    expect($complaint->categoryEnum())->toBe(ComplaintCategory::Listrik);
});
