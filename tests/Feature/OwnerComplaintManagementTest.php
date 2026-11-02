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
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');

    // 1. Setup Owner A (Legitimate Owner)
    $this->ownerA = User::create([
        'name' => 'Owner Alpha',
        'email' => 'owner_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // 2. Setup Property A1 & A2 for Owner A
    $this->propertyA1 = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Alpha Prima',
        'slug' => 'kos-alpha-prima-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Merdeka No. 10',
        'city' => 'Jakarta Barat',
        'district' => 'Grogol',
        'postal_code' => '11440',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    $this->propertyA2 = Property::create([
        'owner_id' => $this->ownerA->id,
        'name' => 'Kos Alpha Suites',
        'slug' => 'kos-alpha-suites-'.Str::random(8),
        'type' => 'putra',
        'address' => 'Jl. Kebon Jeruk No. 20',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 4,
        'status' => 'aktif',
    ]);

    // Rooms for Property A1
    $this->roomA1 = Room::create([
        'property_id' => $this->propertyA1->id,
        'room_number' => '101',
        'type' => 'deluxe',
        'price' => 1500000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    $this->roomA2 = Room::create([
        'property_id' => $this->propertyA1->id,
        'room_number' => '102',
        'type' => 'standard',
        'price' => 1200000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    $this->roomA3 = Room::create([
        'property_id' => $this->propertyA2->id,
        'room_number' => '201',
        'type' => 'standard',
        'price' => 1300000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 3. Setup Owner B (Different Owner for isolation testing)
    $this->ownerB = User::create([
        'name' => 'Owner Beta',
        'email' => 'owner_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    $this->propertyB = Property::create([
        'owner_id' => $this->ownerB->id,
        'name' => 'Kos Beta Residence',
        'slug' => 'kos-beta-'.Str::random(8),
        'type' => 'putri',
        'address' => 'Jl. Sudirman No. 50',
        'city' => 'Jakarta Pusat',
        'district' => 'Menteng',
        'postal_code' => '10310',
        'total_rooms' => 6,
        'status' => 'aktif',
    ]);

    $this->roomB = Room::create([
        'property_id' => $this->propertyB->id,
        'room_number' => '201',
        'type' => 'vip',
        'price' => 2000000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 4. Setup Tenant Users
    $this->tenantA = User::create([
        'name' => 'Penyewa Alpha Satu',
        'email' => 'tenant_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractA = Contract::create([
        'contract_number' => 'CTR-A-'.Str::random(6),
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'start_date' => Carbon::today()->subMonths(1),
        'end_date' => Carbon::today()->addMonths(11),
        'duration_months' => 12,
        'monthly_rent' => 1500000,
        'total_amount' => 18000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    $this->tenantB = User::create([
        'name' => 'Penyewa Beta Satu',
        'email' => 'tenant_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    $this->contractB = Contract::create([
        'contract_number' => 'CTR-B-'.Str::random(6),
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'start_date' => Carbon::today()->subMonths(1),
        'end_date' => Carbon::today()->addMonths(11),
        'duration_months' => 12,
        'monthly_rent' => 2000000,
        'total_amount' => 24000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    $this->complaintService = app(ComplaintService::class);
});

test('1. owner can view complaint list for properties they own', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1001',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'AC kamar 101 tidak dingin',
        'description' => 'Freon AC sepertinya habis.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index'));

    $response->assertOk();
    $response->assertViewIs('owner.complaints.index');
    $response->assertSee('KMP-2026-1001');
    $response->assertSee('AC kamar 101 tidak dingin');
    $response->assertSee('Penyewa Alpha Satu');
    $response->assertSee('Kos Alpha Prima');
});

test('2. complaints from other owners properties do not appear in list', function () {
    $complaintA = Complaint::create([
        'ticket_number' => 'KMP-2026-1002',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Lampu teras mati',
        'description' => 'Lampu depan mati sejak kemarin.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    $complaintB = Complaint::create([
        'ticket_number' => 'KMP-2026-9999',
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Kran wastafel bocor di kos Beta',
        'description' => 'Air menetes terus di kamar mandi.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index'));

    $response->assertOk();
    $response->assertSee('KMP-2026-1002');
    $response->assertSee('Lampu teras mati');
    // Ensure complaint of Owner B is completely excluded
    $response->assertDontSee('KMP-2026-9999');
    $response->assertDontSee('Kran wastafel bocor di kos Beta');
});

test('3. owner can view complaint detail for their own property', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1003',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Pintu balkon susah ditutup',
        'description' => 'Gagang pintu agak seret dan kunci macet.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
        'handled_by' => 'Tukang Slamet',
        'estimated_completion' => Carbon::tomorrow(),
    ]);

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.show', $complaint));

    $response->assertOk();
    $response->assertViewIs('owner.complaints.show');
    $response->assertSee('KMP-2026-1003');
    $response->assertSee('Pintu balkon susah ditutup');
    $response->assertSee('Gagang pintu agak seret dan kunci macet.');
    $response->assertSee('Penyewa Alpha Satu');
    $response->assertSee('Tukang Slamet');
});

test('4. owner cannot view complaint detail for another owners property (idor 403)', function () {
    $complaintB = Complaint::create([
        'ticket_number' => 'KMP-2026-9998',
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Wifi mati di kos Beta',
        'description' => 'Router lantai 2 restart terus.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Owner A attempts to inspect Complaint B -> forbidden
    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.show', $complaintB));

    $response->assertForbidden();
});

test('5. owner can post reply to active open ticket', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1004',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Genteng bocor',
        'description' => 'Air menetes saat hujan deras.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.complaints.replies.store', $complaint), [
            'message' => 'Baik, tukang atap kami akan datang besok pagi pukul 09:00.',
        ]);

    $response->assertRedirect(route('owner.complaints.show', $complaint));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('complaint_replies', [
        'complaint_id' => $complaint->id,
        'user_id' => $this->ownerA->id,
        'message' => 'Baik, tukang atap kami akan datang besok pagi pukul 09:00.',
    ]);
});

test('6. reply records correct user_id of the owner', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1005',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Keran wastafel patah',
        'description' => 'Keran patah tidak bisa ditutup.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $this->actingAs($this->ownerA)
        ->post(route('owner.complaints.replies.store', $complaint), [
            'message' => 'Sudah kami belikan kran baru tipe kuningan.',
        ]);

    $reply = ComplaintReply::where('complaint_id', $complaint->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->user_id)->toBe($this->ownerA->id);
    expect($reply->isFromOwner())->toBeTrue();
    expect($reply->isFromTenant())->toBeFalse();
});

test('7. replies appear in chronological order', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1006',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kloset mampet',
        'description' => 'Air flush tidak turun.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $reply1 = ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->tenantA->id,
        'message' => 'Pesan 1: Tolong dibantu pak kos.',
        'created_at' => Carbon::now()->subMinutes(20),
    ]);

    $reply2 = ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->ownerA->id,
        'message' => 'Pesan 2: Teknisi sedot WC sedang meluncur.',
        'created_at' => Carbon::now()->subMinutes(10),
    ]);

    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.show', $complaint));

    $response->assertOk();
    $replies = $response->viewData('complaint')->replies;
    expect($replies->first()->id)->toBe($reply1->id);
    expect($replies->last()->id)->toBe($reply2->id);
});

test('8. closed ticket rejects new replies from owner and tenant', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1007',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kunci kamar hilang',
        'description' => 'Perlu duplikat kunci.',
        'status' => ComplaintStatus::Ditutup->value,
        'closed_at' => Carbon::now()->subDays(1),
    ]);

    // Owner reply attempt on closed ticket is forbidden by request / policy
    $response = $this->actingAs($this->ownerA)
        ->post(route('owner.complaints.replies.store', $complaint), [
            'message' => 'Mencoba membalas tiket yang sudah ditutup.',
        ]);

    $response->assertForbidden();
    expect(ComplaintReply::where('complaint_id', $complaint->id)->count())->toBe(0);
});

test('9. owner can update status according to lifecycle transition rules', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1008',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Stop kontak bau gosong',
        'description' => 'Ada bau sangit dari colokan meja belajar.',
        'status' => ComplaintStatus::Baru->value,
    ]);

    // 1. Baru -> Diproses
    $response1 = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'diproses',
            'handled_by' => 'Pak Joko (Teknisi Listrik)',
            'estimated_completion' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

    $response1->assertRedirect(route('owner.complaints.show', $complaint));
    $complaint->refresh();
    expect($complaint->status)->toBe('diproses');
    expect($complaint->handled_by)->toBe('Pak Joko (Teknisi Listrik)');
    expect($complaint->estimated_completion)->not->toBeNull();

    // 2. Diproses -> Menunggu Tanggapan
    $response2 = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'menunggu_tanggapan',
        ]);

    $response2->assertRedirect(route('owner.complaints.show', $complaint));
    $complaint->refresh();
    expect($complaint->status)->toBe('menunggu_tanggapan');

    // 3. Menunggu Tanggapan -> Selesai
    $response3 = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'selesai',
        ]);

    $response3->assertRedirect(route('owner.complaints.show', $complaint));
    $complaint->refresh();
    expect($complaint->status)->toBe('selesai');
    expect($complaint->resolved_at)->not->toBeNull();

    // 4. Selesai -> Ditutup
    $response4 = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'ditutup',
        ]);

    $response4->assertRedirect(route('owner.complaints.show', $complaint));
    $complaint->refresh();
    expect($complaint->status)->toBe('ditutup');
    expect($complaint->closed_at)->not->toBeNull();
});

test('10. illegal status transition or invalid status value is rejected', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1009',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Cat dinding mengelupas',
        'description' => 'Bagian bawah catnya rontok.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    // A. Invalid non-existent status string
    $responseA = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'status_tidak_jelas',
        ]);

    $responseA->assertSessionHasErrors(['status']);

    // B. Attempting to revert already handled ticket back to 'baru'
    $responseB = $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'baru',
        ]);

    $responseB->assertSessionHasErrors(['status']);
    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Diproses->value);
});

test('11. timestamps resolved_at and closed_at are correctly set and consistent with service', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1010',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Gagang shower bocor',
        'description' => 'Selang shower pecah.',
        'status' => ComplaintStatus::Diproses->value,
        'resolved_at' => null,
        'closed_at' => null,
    ]);

    // Transition to Selesai sets resolved_at
    $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'selesai',
        ]);

    $complaint->refresh();
    expect($complaint->resolved_at)->not->toBeNull();
    expect($complaint->closed_at)->toBeNull();

    // Transition from Selesai back to Diproses resets resolved_at
    $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'diproses',
        ]);

    $complaint->refresh();
    expect($complaint->resolved_at)->toBeNull();

    // Transition to Ditutup sets closed_at
    $this->actingAs($this->ownerA)
        ->patch(route('owner.complaints.status.update', $complaint), [
            'status' => 'ditutup',
        ]);

    $complaint->refresh();
    expect($complaint->closed_at)->not->toBeNull();
});

test('12. id manipulation and parameter tampering does not bypass authorization', function () {
    $complaintA = Complaint::create([
        'ticket_number' => 'KMP-2026-1011',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Exhaust fan mati',
        'description' => 'Kipas tidak berputar.',
        'status' => ComplaintStatus::Diproses->value,
    ]);

    // 1. Owner B attempts to update status of Complaint A
    $response1 = $this->actingAs($this->ownerB)
        ->patch(route('owner.complaints.status.update', $complaintA), [
            'status' => 'selesai',
        ]);
    $response1->assertForbidden();

    // 2. Owner B attempts to post reply to Complaint A
    $response2 = $this->actingAs($this->ownerB)
        ->post(route('owner.complaints.replies.store', $complaintA), [
            'message' => 'Pesan dari penyusup.',
        ]);
    $response2->assertForbidden();

    // 3. Tenant attempts to access Owner status endpoint
    $response3 = $this->actingAs($this->tenantA)
        ->patch(route('owner.complaints.status.update', $complaintA), [
            'status' => 'selesai',
        ]);
    $response3->assertForbidden();
});

test('13. filtering, searching, and pagination work without data leakage across owners', function () {
    // 3 complaints for Owner A (across 2 different properties)
    $compA1 = Complaint::create([
        'ticket_number' => 'KMP-2026-1020',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kran wastafel macet',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
        'description' => 'Masalah air.',
    ]);

    $compA2 = Complaint::create([
        'ticket_number' => 'KMP-2026-1021',
        'property_id' => $this->propertyA2->id,
        'room_id' => $this->roomA3->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Lampu pagar padam',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Diproses->value,
        'description' => 'Masalah listrik pagar.',
    ]);

    // 1 complaint for Owner B with similar keyword
    $compB1 = Complaint::create([
        'ticket_number' => 'KMP-2026-1022',
        'property_id' => $this->propertyB->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Kran wastafel bocor parah di kos beta',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Darurat->value,
        'status' => ComplaintStatus::Baru->value,
        'description' => 'Masalah air beta.',
    ]);

    // Test A: Filter by property_id
    $resProp = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index', ['property_id' => $this->propertyA1->id]));
    $resProp->assertOk();
    $resProp->assertSee('KMP-2026-1020');
    $resProp->assertDontSee('KMP-2026-1021');
    $resProp->assertDontSee('KMP-2026-1022');

    // Test B: Filter by category
    $resCat = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index', ['category' => 'Listrik']));
    $resCat->assertOk();
    $resCat->assertSee('KMP-2026-1021');
    $resCat->assertDontSee('KMP-2026-1020');
    $resCat->assertDontSee('KMP-2026-1022');

    // Test C: Filter by priority
    $resPrio = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index', ['priority' => 'tinggi']));
    $resPrio->assertOk();
    $resPrio->assertSee('KMP-2026-1021');
    $resPrio->assertDontSee('KMP-2026-1020');

    // Test D: Search by keyword 'wastafel'
    $resSearch = $this->actingAs($this->ownerA)
        ->get(route('owner.complaints.index', ['q' => 'wastafel']));
    $resSearch->assertOk();
    $resSearch->assertSee('KMP-2026-1020');
    // Ensure Owner B's matching wastafel complaint is NEVER leaked
    $resSearch->assertDontSee('KMP-2026-1022');
    $resSearch->assertDontSee('kos beta');
});

test('14. complaint photo attachment can only be viewed/downloaded by property owner and ticket tenant', function () {
    $path = 'complaints/bukti_rusak.jpg';
    Storage::disk('local')->put($path, 'fake-image-binary-content');

    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-1030',
        'property_id' => $this->propertyA1->id,
        'room_id' => $this->roomA1->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Plafon retak berair',
        'description' => 'Ada rembesan dari lantai atas.',
        'status' => ComplaintStatus::Baru->value,
        'photo_url' => $path,
    ]);

    // 1. Owner of the property can view photo
    $resOwner = $this->actingAs($this->ownerA)
        ->get(route('complaints.photo', $complaint));
    $resOwner->assertOk();

    // 2. Tenant who filed it can view photo
    $resTenant = $this->actingAs($this->tenantA)
        ->get(route('complaints.photo', $complaint));
    $resTenant->assertOk();

    // 3. Different Owner cannot view photo (403 Forbidden)
    $resOtherOwner = $this->actingAs($this->ownerB)
        ->get(route('complaints.photo', $complaint));
    $resOtherOwner->assertForbidden();

    // 4. Different Tenant cannot view photo (403 Forbidden)
    $resOtherTenant = $this->actingAs($this->tenantB)
        ->get(route('complaints.photo', $complaint));
    $resOtherTenant->assertForbidden();

    // 5. Owner can download photo with ?download=1
    $resDownload = $this->actingAs($this->ownerA)
        ->get(route('complaints.photo', ['complaint' => $complaint, 'download' => 1]));
    $resDownload->assertOk();
    $resDownload->assertHeader('content-disposition');
});

test('15. sidebar navigation item for complaints is active and routed correctly', function () {
    $response = $this->actingAs($this->ownerA)
        ->get(route('owner.dashboard'));

    $response->assertOk();
    $response->assertSee(route('owner.complaints.index'));
    $response->assertSee('Komplain');
});
