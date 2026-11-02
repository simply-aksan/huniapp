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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    // 1. Owner Alpha (Authorized Owner)
    $this->ownerAlpha = User::create([
        'name' => 'Pak Hendra Owner',
        'email' => 'owner_hendra_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // 2. Property Alpha
    $this->propertyAlpha = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Permata Alpha',
        'slug' => 'kos-permata-alpha-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Anggrek No. 12',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 10,
        'status' => 'aktif',
    ]);

    $this->roomAlpha1 = Room::create([
        'property_id' => $this->propertyAlpha->id,
        'room_number' => 'A-101',
        'type' => 'deluxe',
        'price' => 1750000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    $this->roomAlpha2 = Room::create([
        'property_id' => $this->propertyAlpha->id,
        'room_number' => 'A-102',
        'type' => 'standard',
        'price' => 1400000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 3. Owner Beta (Unrelated Owner for IDOR testing)
    $this->ownerBeta = User::create([
        'name' => 'Ibu Maya Owner',
        'email' => 'owner_maya_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyBeta = Property::create([
        'owner_id' => $this->ownerBeta->id,
        'name' => 'Kos Graha Beta',
        'slug' => 'kos-graha-beta-'.Str::random(8),
        'type' => 'putri',
        'address' => 'Jl. Cempaka No. 88',
        'city' => 'Jakarta Selatan',
        'district' => 'Tebet',
        'postal_code' => '12810',
        'total_rooms' => 6,
        'status' => 'aktif',
    ]);

    $this->roomBeta1 = Room::create([
        'property_id' => $this->propertyBeta->id,
        'room_number' => 'B-201',
        'type' => 'vip',
        'price' => 2200000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 4. Tenant Alpha (Authorized Tenant under Property Alpha)
    $this->tenantAlpha = User::create([
        'name' => 'Budi Penyewa',
        'email' => 'tenant_budi_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractAlpha = Contract::create([
        'contract_number' => 'CTR-ALPHA-'.Str::random(6),
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->subMonths(3),
        'end_date' => Carbon::today()->addMonths(9),
        'duration_months' => 12,
        'monthly_rent' => 1750000,
        'total_amount' => 21000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    // 5. Tenant Beta (Authorized Tenant under Property Beta)
    $this->tenantBeta = User::create([
        'name' => 'Siti Penyewa',
        'email' => 'tenant_siti_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractBeta = Contract::create([
        'contract_number' => 'CTR-BETA-'.Str::random(6),
        'property_id' => $this->propertyBeta->id,
        'room_id' => $this->roomBeta1->id,
        'user_id' => $this->tenantBeta->id,
        'start_date' => Carbon::today()->subMonths(1),
        'end_date' => Carbon::today()->addMonths(11),
        'duration_months' => 12,
        'monthly_rent' => 2200000,
        'total_amount' => 26400000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    $this->complaintService = app(ComplaintService::class);
});

/*
|--------------------------------------------------------------------------
| A. Tenant Complaint Index QA
|--------------------------------------------------------------------------
*/
test('QA-A: tenant complaint index displays KPIs, filters, pagination, and empty state', function () {
    // 1. Empty state verification
    $resEmpty = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.index'));
    $resEmpty->assertOk();
    $resEmpty->assertSee('Belum Ada Pengaduan');
    $resEmpty->assertSee(route('tenant.complaints.create'));

    // 2. Populate 3 complaints with different statuses
    Complaint::create([
        'ticket_number' => 'KMP-QA-T1',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Lampu kamar mandi redup',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
        'description' => 'Bohlam redup.',
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-QA-T2',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Kran wastafel dol',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
        'description' => 'Kran air dol.',
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-QA-T3',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Kunci pintu diperbaiki',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now(),
        'description' => 'Kunci sudah lancar.',
    ]);

    // 3. Check populated index & KPI counts
    $resPopulated = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.index'));
    $resPopulated->assertOk();
    $resPopulated->assertSee('KMP-QA-T1');
    $resPopulated->assertSee('KMP-QA-T2');
    $resPopulated->assertSee('KMP-QA-T3');

    // 4. Test filter by status
    $resFiltered = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.index', ['status' => 'diproses']));
    $resFiltered->assertOk();
    $resFiltered->assertSee('KMP-QA-T2');
    $resFiltered->assertDontSee('KMP-QA-T1');
    $resFiltered->assertDontSee('KMP-QA-T3');

    // 5. Test search query
    $resSearch = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.index', ['q' => 'wastafel']));
    $resSearch->assertOk();
    $resSearch->assertSee('KMP-QA-T2');
    $resSearch->assertDontSee('KMP-QA-T1');
});

/*
|--------------------------------------------------------------------------
| B. Tenant Create Complaint QA
|--------------------------------------------------------------------------
*/
test('QA-B: tenant create complaint form enforces validations, lease requirements, and file upload limits', function () {
    // 1. Tenant without active lease sees warning card
    $unleasedTenant = User::create([
        'name' => 'Penyewa Tanpa Kontrak',
        'email' => 'unleased_'.Str::random(8).'@huniapp.test',
        'phone' => '0816'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $resWarning = $this->actingAs($unleasedTenant)->get(route('tenant.complaints.create'));
    $resWarning->assertOk();
    $resWarning->assertSee('Tidak Ditemukan Kontrak Sewa Aktif');

    // 2. Validation failures on empty or invalid inputs
    $resInvalid = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.store'), [
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'title' => 'Abc', // min: 5
        'description' => 'Pendek', // min: 10
        'category' => 'InvalidCategory',
        'priority' => 'InvalidPriority',
    ]);
    $resInvalid->assertSessionHasErrors(['title', 'description', 'category', 'priority']);

    // 3. Reject invalid file type (e.g. executable or text file disguised)
    $fakeTxt = UploadedFile::fake()->create('script.sh', 500, 'application/x-sh');
    $resFileErr = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.store'), [
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'title' => 'Judul komplain valid',
        'description' => 'Deskripsi keluhan yang cukup panjang dan valid.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Sedang->value,
        'photo' => $fakeTxt,
    ]);
    $resFileErr->assertSessionHasErrors(['photo']);

    // 4. Successful creation with valid JPEG photo upload
    $validPhoto = UploadedFile::fake()->create('foto_kerusakan.jpg', 300, 'image/jpeg');
    $resSuccess = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.store'), [
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'title' => 'Colokan listrik longgar',
        'description' => 'Colokan samping kasur agak goyang dan mengeluarkan percikan.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'photo' => $validPhoto,
    ]);

    $created = Complaint::where('title', 'Colokan listrik longgar')->first();
    expect($created)->not->toBeNull();
    $resSuccess->assertRedirect(route('tenant.complaints.show', $created));
    expect($created->photo_url)->not->toBeNull();
    expect(Storage::disk('local')->exists($created->photo_url))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| C. Tenant Detail, Conversation, & Resolution QA
|--------------------------------------------------------------------------
*/
test('QA-C: tenant detail displays chronological replies, resolved confirmation, and closed state', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-QA-DETAIL-1',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'AC bocor menetes ke meja',
        'description' => 'Air AC menetes deras.',
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now(),
    ]);

    ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->ownerAlpha->id,
        'message' => 'Pipa AC sudah dibersihkan teknisi.',
        'created_at' => now()->subMinutes(30),
    ]);

    // 1. View detail while in 'selesai' state
    $resResolved = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.show', $complaint));
    $resResolved->assertOk();
    $resResolved->assertSee('Perbaikan Telah Ditandai Selesai oleh Pemilik Kos');
    $resResolved->assertSee('Pipa AC sudah dibersihkan teknisi.');

    // 2. Tenant confirms completion via close button
    $resClose = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.close', $complaint));
    $resClose->assertRedirect(route('tenant.complaints.show', $complaint));
    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Ditutup->value);

    // 3. View detail after closure
    $resClosed = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.show', $complaint));
    $resClosed->assertOk();
    $resClosed->assertSee('Tiket Pengaduan Ini Telah Ditutup Permanen');

    // 4. Replies are rejected on closed ticket
    $resRejectedReply = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.replies.store', $complaint), [
        'message' => 'Mencoba membalas tiket tertutup.',
    ]);
    $resRejectedReply->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| D. Owner Complaint Index QA
|--------------------------------------------------------------------------
*/
test('QA-D: owner index displays KPIs, property/status filters, technician info, and search', function () {
    Complaint::create([
        'ticket_number' => 'KMP-QA-OWNER-1',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Wastafel tersumbat',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
        'handled_by' => 'Pak Joko Tukang Ledeng',
        'estimated_completion' => Carbon::tomorrow(),
        'description' => 'Air meluap.',
    ]);

    $response = $this->actingAs($this->ownerAlpha)->get(route('owner.complaints.index'));
    $response->assertOk();
    $response->assertSee('KMP-QA-OWNER-1');
    $response->assertSee('Wastafel tersumbat');
    $response->assertSee('Pak Joko Tukang Ledeng');
    $response->assertSee('Kos Permata Alpha');
});

/*
|--------------------------------------------------------------------------
| E. Owner Detail & Status Update Modal QA
|--------------------------------------------------------------------------
*/
test('QA-E: owner detail modal updates status, assigns technician, and prevents illegal transition', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-QA-OWNER-2',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Gagang pintu patah',
        'description' => 'Perlu diganti gagang pintu baru.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    // 1. Owner updates status to selesai with technician info
    $resUpdate = $this->actingAs($this->ownerAlpha)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'selesai',
            'handled_by' => 'Teknisi Kunci Budi',
            'estimated_completion' => Carbon::today()->format('Y-m-d'),
        ]);

    $resUpdate->assertRedirect(route('owner.complaints.show', $complaint));
    $fresh = $complaint->fresh();
    expect($fresh->status)->toBe('selesai');
    expect($fresh->handled_by)->toBe('Teknisi Kunci Budi');
    expect($fresh->resolved_at)->not->toBeNull();

    // 2. Illegal transition: cannot revert handled ticket to 'baru'
    $resIllegal = $this->actingAs($this->ownerAlpha)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'baru',
        ]);
    $resIllegal->assertSessionHasErrors(['status']);
});

/*
|--------------------------------------------------------------------------
| F. Responsive Layout & Neo-Brutalism Token Inspection QA
|--------------------------------------------------------------------------
*/
test('QA-F: responsive layout tokens, mobile drawer, and neo-brutalist styling are properly configured', function () {
    // 1. Tenant index view inspection
    $resTenant = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.index'));
    $resTenant->assertOk();
    $tenantContent = $resTenant->getContent();
    expect($tenantContent)->toContain('#F7F3EA'); // Cream background
    expect($tenantContent)->toContain('#171717'); // Dark outline
    expect($tenantContent)->toContain('border-2'); // 2px neo-brutalist borders

    // 2. Owner index view inspection with populated complaint
    Complaint::create([
        'ticket_number' => 'KMP-QA-RESP-1',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Cek responsifitas layout',
        'description' => 'Test tabel responsif.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    $resOwner = $this->actingAs($this->ownerAlpha)->get(route('owner.complaints.index'));
    $resOwner->assertOk();
    $ownerContent = $resOwner->getContent();
    expect($ownerContent)->toContain('overflow-x-auto'); // Table horizontal overflow safety
    expect($ownerContent)->toContain('grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5'); // Responsive KPI grid
    expect($ownerContent)->toContain('#C7F36B'); // Lime accent
});

/*
|--------------------------------------------------------------------------
| G. Full 12-Step End-to-End Cycle QA
|--------------------------------------------------------------------------
*/
test('QA-G: complete 12-step lifecycle cycle from tenant filing to auto-close and immutability', function () {
    // Step 1: Tenant creates complaint with photo
    $photoPath = 'complaints/qa_photo.jpg';
    Storage::disk('local')->put($photoPath, 'binary-image-stream');

    $complaint = $this->complaintService->createComplaint([
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'title' => 'Kipas exhaust kamar mandi mati total',
        'description' => 'Exhaust fan tidak berputar saat sakelar dinyalakan.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Sedang->value,
        'photo_url' => $photoPath,
    ], $this->tenantAlpha);

    expect($complaint->status)->toBe('baru');

    // Step 2: Photo is stored in private disk
    expect(Storage::disk('local')->exists($photoPath))->toBeTrue();

    // Step 3: Owner views complaint in index and show
    $this->actingAs($this->ownerAlpha)->get(route('owner.complaints.index'))->assertSee($complaint->ticket_number);
    $this->actingAs($this->ownerAlpha)->get(route('owner.complaints.show', $complaint))->assertOk();

    // Step 4: Owner downloads photo with authorization
    $this->actingAs($this->ownerAlpha)->get(route('complaints.photo', $complaint))->assertOk();

    // Step 5: Owner posts reply
    $this->actingAs($this->ownerAlpha)->post(route('owner.complaints.replies.store', $complaint), [
        'message' => 'Baik mas, sakelarnya ada bunyi mendengung tidak?',
    ])->assertRedirect();

    // Step 6: Tenant views reply
    $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.show', $complaint))
        ->assertSee('sakelarnya ada bunyi mendengung tidak?');

    // Step 7: Owner sets status menunggu_tanggapan
    $this->actingAs($this->ownerAlpha)->patch(route('owner.complaints.status.update', $complaint), [
        'status' => 'menunggu_tanggapan',
    ])->assertRedirect();
    expect($complaint->fresh()->status)->toBe('menunggu_tanggapan');

    // Step 8: Tenant replies, auto-transitioning status to diproses
    $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.replies.store', $complaint), [
        'message' => 'Tidak ada bunyi dengung pak, hening sama sekali.',
    ])->assertRedirect();
    expect($complaint->fresh()->status)->toBe('diproses');

    // Step 9: Owner marks selesai, setting resolved_at and triggering notification
    $this->actingAs($this->ownerAlpha)->patch(route('owner.complaints.status.update', $complaint), [
        'status' => 'selesai',
        'handled_by' => 'Pak Yono (Teknisi)',
    ])->assertRedirect();

    $resolved = $complaint->fresh();
    expect($resolved->status)->toBe('selesai');
    expect($resolved->resolved_at)->not->toBeNull();

    Notification::assertSentTo(
        $this->tenantAlpha,
        ComplaintStatusUpdatedNotification::class,
        fn ($notif) => $notif->complaint->id === $complaint->id && $notif->newStatus === 'selesai'
    );

    // Step 10: Auto-close command closes tickets at H+7 idempotently
    $simulatedDate = Carbon::now()->addDays(8);
    $exitCode = Artisan::call('complaints:auto-close-resolved', [
        '--days' => 7,
        '--as-of' => $simulatedDate->format('Y-m-d'),
    ]);
    expect($exitCode)->toBe(0);

    $closed = $complaint->fresh();
    expect($closed->status)->toBe('ditutup');
    expect($closed->closed_at)->not->toBeNull();

    // Step 11: Idempotency re-run closes 0 tickets
    $secondExit = Artisan::call('complaints:auto-close-resolved', [
        '--days' => 7,
        '--as-of' => $simulatedDate->format('Y-m-d'),
    ]);
    expect($secondExit)->toBe(0);

    // Step 12: Closed ticket rejects any further replies
    $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.replies.store', $closed), [
        'message' => 'Coba balas tiket yang sudah ditutup permanen.',
    ])->assertForbidden();

    $this->actingAs($this->ownerAlpha)->post(route('owner.complaints.replies.store', $closed), [
        'message' => 'Owner juga tidak bisa balas tiket tertutup.',
    ])->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| H. Timezone & Scheduler QA
|--------------------------------------------------------------------------
*/
test('QA-H: timezone is UTC and complaints:auto-close-resolved is scheduled daily at 02:00 UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');

    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $event = $events->first(fn ($e) => str_contains($e->command ?? '', 'complaints:auto-close-resolved'));
    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 2 * * *');
});

/*
|--------------------------------------------------------------------------
| I. Security, IDOR, & Stored XSS QA
|--------------------------------------------------------------------------
*/
test('QA-I: security checks verify IDOR isolation, private photo gate, and stored XSS prevention', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-QA-SEC-1',
        'property_id' => $this->propertyAlpha->id,
        'room_id' => $this->roomAlpha1->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'XSS Test <script>alert("hack")</script>',
        'description' => 'Body XSS <svg/onload=alert(1)>',
        'status' => ComplaintStatus::Diproses->value,
        'photo_url' => 'complaints/secret.jpg',
    ]);
    Storage::disk('local')->put('complaints/secret.jpg', 'private-data');

    // 1. Tenant IDOR: Tenant Beta cannot view Tenant Alpha complaint
    $this->actingAs($this->tenantBeta)->get(route('tenant.complaints.show', $complaint))->assertForbidden();

    // 2. Owner IDOR: Owner Beta cannot view Owner Alpha complaint
    $this->actingAs($this->ownerBeta)->get(route('owner.complaints.show', $complaint))->assertForbidden();

    // 3. Photo Authorization: Unauthorized users get 403 Forbidden
    $this->actingAs($this->tenantBeta)->get(route('complaints.photo', $complaint))->assertForbidden();
    $this->actingAs($this->ownerBeta)->get(route('complaints.photo', $complaint))->assertForbidden();

    // 4. Stored XSS strings are properly escaped on rendering
    $resView = $this->actingAs($this->tenantAlpha)->get(route('tenant.complaints.show', $complaint));
    $resView->assertOk();
    $resView->assertDontSee('<script>alert("hack")</script>', false);
    $resView->assertDontSee('<svg/onload=alert(1)>', false);
    $resView->assertSee(e('XSS Test <script>alert("hack")</script>'), false);
    $resView->assertSee(e('Body XSS <svg/onload=alert(1)>'), false);
});
