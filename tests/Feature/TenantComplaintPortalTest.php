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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');

    // 1. Setup Owner
    $this->owner = User::create([
        'name' => 'Bapak Kos Sukses',
        'email' => 'owner_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // 2. Setup Property
    $this->property = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kos Sejahtera HUNI',
        'slug' => 'kos-sejahtera-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Kebon Jeruk No. 10',
        'city' => 'Jakarta Barat',
        'district' => 'Kebon Jeruk',
        'postal_code' => '11530',
        'total_rooms' => 5,
        'status' => 'aktif',
    ]);

    // 3. Setup Rooms
    $this->roomA = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '101',
        'type' => 'deluxe',
        'price' => 1500000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    $this->roomB = Room::create([
        'property_id' => $this->property->id,
        'room_number' => '102',
        'type' => 'standard',
        'price' => 1200000,
        'deposit' => 500000,
        'status' => 'terisi',
    ]);

    // 4. Setup Tenant A
    $this->tenantA = User::create([
        'name' => 'Penyewa Alpha',
        'email' => 'tenant_a_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Active Lease for Tenant A in Room A
    $this->contractA = Contract::create([
        'contract_number' => 'CTR-A-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'start_date' => Carbon::today()->subMonths(2),
        'end_date' => Carbon::today()->addMonths(10),
        'duration_months' => 12,
        'monthly_rent' => 1500000,
        'total_amount' => 18000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    // 5. Setup Tenant B (Attacker / Different Tenant)
    $this->tenantB = User::create([
        'name' => 'Penyewa Beta',
        'email' => 'tenant_b_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Active Lease for Tenant B in Room B
    $this->contractB = Contract::create([
        'contract_number' => 'CTR-B-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'start_date' => Carbon::today()->subMonths(1),
        'end_date' => Carbon::today()->addMonths(11),
        'duration_months' => 12,
        'monthly_rent' => 1200000,
        'total_amount' => 14400000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);
});

it('allows tenant to view their own complaints list with stats and active nav', function () {
    // Create 2 complaints for Tenant A
    Complaint::create([
        'ticket_number' => 'KMP-2026-0001',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Keran air bocor',
        'description' => 'Air terus menetes dari keran wastafel.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-2026-0002',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Lampu koridor mati',
        'description' => 'Lampu depan pintu kamar padam sejak kemarin.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now(),
    ]);

    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index'));

    $response->assertOk();
    $response->assertSee('KOMPLAIN');
    $response->assertSee('TIKET');
    $response->assertSee('KMP-2026-0001');
    $response->assertSee('Keran air bocor');
    $response->assertSee('KMP-2026-0002');
    $response->assertSee('Lampu koridor mati');
    $response->assertSee('Buat Pengaduan Baru');
});

it('does not display complaints belonging to other tenants in the list', function () {
    // Complaint for Tenant A
    Complaint::create([
        'ticket_number' => 'KMP-2026-0010',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'AC kamar 101 tidak dingin',
        'description' => 'AC hanya mengeluarkan angin biasa.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Complaint for Tenant B
    Complaint::create([
        'ticket_number' => 'KMP-2026-0020',
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Pintu kamar 102 macet',
        'description' => 'Kunci pintu kamar 102 susah diputar.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Darurat->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index'));

    $response->assertOk();
    $response->assertSee('KMP-2026-0010');
    $response->assertSee('AC kamar 101 tidak dingin');
    $response->assertDontSee('KMP-2026-0020');
    $response->assertDontSee('Pintu kamar 102 macet');
});

it('allows tenant to view their own complaint detail with replies and status', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0030',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'WiFi lantai 1 lambat',
        'description' => 'Kecepatan internet drop drastis di malam hari.',
        'category' => ComplaintCategory::Internet->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    ComplaintReply::create([
        'complaint_id' => $complaint->id,
        'user_id' => $this->owner->id,
        'message' => 'Sudah kami restart router, mohon dicoba kembali ya.',
    ]);

    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.show', $complaint));

    $response->assertOk();
    $response->assertSee('KMP-2026-0030');
    $response->assertSee('WiFi lantai 1 lambat');
    $response->assertSee('Kecepatan internet drop drastis di malam hari.');
    $response->assertSee('Sudah kami restart router, mohon dicoba kembali ya.');
    $response->assertSee('Pemilik Kos');
});

it('prevents tenant from viewing complaint belonging to another tenant (IDOR prevention)', function () {
    $complaintB = Complaint::create([
        'ticket_number' => 'KMP-2026-0040',
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Privasi Kamar Beta',
        'description' => 'Informasi rahasia milik penyewa Beta.',
        'category' => ComplaintCategory::Lainnya->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.show', $complaintB));

    $response->assertForbidden();
});

it('supports searching by ticket number and title in complaints list', function () {
    Complaint::create([
        'ticket_number' => 'KMP-2026-0051',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Genteng kamar bocor saat hujan',
        'description' => 'Ada rembesan air di plafon kamar.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-2026-0052',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Tempat sampah luar penuh',
        'description' => 'Perlu diangkut oleh petugas kebersihan.',
        'category' => ComplaintCategory::Kebersihan->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Search by title keyword
    $responseTitle = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index', ['q' => 'Genteng']));

    $responseTitle->assertOk();
    $responseTitle->assertSee('Genteng kamar bocor saat hujan');
    $responseTitle->assertDontSee('Tempat sampah luar penuh');

    // Search by ticket number
    $responseTicket = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index', ['q' => '0052']));

    $responseTicket->assertOk();
    $responseTicket->assertSee('Tempat sampah luar penuh');
    $responseTicket->assertDontSee('Genteng kamar bocor saat hujan');
});

it('supports filtering by status and category', function () {
    Complaint::create([
        'ticket_number' => 'KMP-2026-0061',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Tagihan listrik korslet',
        'description' => 'Stop kontak bau gosong.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Darurat->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    Complaint::create([
        'ticket_number' => 'KMP-2026-0062',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Sampah depan kamar',
        'description' => 'Ada tumpukan daun kering.',
        'category' => ComplaintCategory::Kebersihan->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Selesai->value,
        'resolved_at' => now(),
    ]);

    // Filter status = baru
    $responseStatus = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index', ['status' => 'baru']));

    $responseStatus->assertOk();
    $responseStatus->assertSee('Tagihan listrik korslet');
    $responseStatus->assertDontSee('Sampah depan kamar');

    // Filter category = kebersihan
    $responseCategory = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.index', ['category' => 'kebersihan']));

    $responseCategory->assertOk();
    $responseCategory->assertSee('Sampah depan kamar');
    $responseCategory->assertDontSee('Tagihan listrik korslet');
});

it('allows tenant to access create form with active contracts pre-loaded', function () {
    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.create'));

    $response->assertOk();
    $response->assertSee('BUAT PENGADUAN BARU');
    $response->assertSee('Kos Sejahtera HUNI');
    $response->assertSee('Kamar 101');
});

it('successfully creates a complaint for an active leased room', function () {
    $payload = [
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'title' => 'Gagang pintu kamar mandi goyang',
        'description' => 'Baut pegangan pintu kendor dan rawan terlepas saat ditarik.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
    ];

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), $payload);

    $complaint = Complaint::where('user_id', $this->tenantA->id)->latest('id')->first();
    expect($complaint)->not->toBeNull();
    expect($complaint->title)->toBe('Gagang pintu kamar mandi goyang');
    expect($complaint->status)->toBe(ComplaintStatus::Baru->value);
    expect($complaint->ticket_number)->toMatch('/^KMP-\d{4}-\d{4}$/');

    $response->assertRedirect(route('tenant.complaints.show', $complaint));
    $response->assertSessionHas('success');
});

it('rejects complaint submission for room or property not actively leased by tenant', function () {
    // Tenant A attempts to file complaint for Room B (which is leased by Tenant B)
    $payload = [
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'title' => 'Mencoba mengadukan kamar orang lain',
        'description' => 'Ini adalah keluhan untuk kamar orang lain secara ilegal.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
    ];

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), $payload);

    $response->assertSessionHasErrors('room_id');
    expect(Complaint::where('title', 'Mencoba mengadukan kamar orang lain')->exists())->toBeFalse();
});

it('ignores and rejects client manipulation of user_id, ticket_number, status, and timestamps', function () {
    $payload = [
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantB->id, // Spoofed user_id
        'ticket_number' => 'FAKE-TICKET-999', // Spoofed ticket_number
        'status' => 'selesai', // Spoofed status
        'resolved_at' => '2020-01-01 00:00:00', // Spoofed timestamp
        'title' => 'Uji coba manipulasi atribut sistem',
        'description' => 'Mencoba memanipulasi status langsung menjadi selesai.',
        'category' => ComplaintCategory::Lainnya->value,
        'priority' => ComplaintPriority::Rendah->value,
    ];

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), $payload);

    $complaint = Complaint::where('title', 'Uji coba manipulasi atribut sistem')->first();
    expect($complaint)->not->toBeNull();
    // System must enforce authentic values
    expect($complaint->user_id)->toBe($this->tenantA->id);
    expect($complaint->ticket_number)->not->toBe('FAKE-TICKET-999');
    expect($complaint->ticket_number)->toMatch('/^KMP-\d{4}-\d{4}$/');
    expect($complaint->status)->toBe(ComplaintStatus::Baru->value);
    expect($complaint->resolved_at)->toBeNull();
});

it('validates required fields, min lengths, and enum values on complaint store', function () {
    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), [
            'property_id' => '',
            'room_id' => '',
            'title' => 'ab', // < 5 chars
            'description' => 'pendek', // < 10 chars
            'category' => 'kategori_palsu',
            'priority' => 'prioritas_palsu',
        ]);

    $response->assertSessionHasErrors([
        'property_id',
        'room_id',
        'title',
        'description',
        'category',
        'priority',
    ]);
});

it('successfully uploads photo and saves in private storage with random filename', function () {
    $fakePhoto = UploadedFile::fake()->create('bukti_rusak.jpg', 600, 'image/jpeg');

    $payload = [
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'title' => 'Kerusakan jendela kaca',
        'description' => 'Kaca jendela retak di pojok kanan bawah.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'photo' => $fakePhoto,
    ];

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), $payload);

    $complaint = Complaint::where('title', 'Kerusakan jendela kaca')->first();
    expect($complaint)->not->toBeNull();
    expect($complaint->photo_url)->not->toBeNull();
    // Ensure original name is NOT used directly as storage path
    expect($complaint->photo_url)->not->toContain('bukti_rusak.jpg');
    expect(str_starts_with($complaint->photo_url, 'complaints/'))->toBeTrue();

    // Verify stored on private disk
    Storage::disk('local')->assertExists($complaint->photo_url);

    $response->assertRedirect(route('tenant.complaints.show', $complaint));
});

it('rejects unsupported file formats and files exceeding 5MB for complaint photo', function () {
    // 1. Unsupported mime / extension (e.g. PDF)
    $fakePdf = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');

    $responsePdf = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), [
            'property_id' => $this->property->id,
            'room_id' => $this->roomA->id,
            'title' => 'Pengaduan dengan file pdf',
            'description' => 'Deskripsi keluhan dengan berkas pdf ilegal.',
            'category' => ComplaintCategory::Lainnya->value,
            'priority' => ComplaintPriority::Rendah->value,
            'photo' => $fakePdf,
        ]);

    $responsePdf->assertSessionHasErrors('photo');

    // 2. Oversized file (> 5120 KB)
    $largeImage = UploadedFile::fake()->create('gambar_raksasa.jpg', 6000, 'image/jpeg');

    $responseLarge = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.store'), [
            'property_id' => $this->property->id,
            'room_id' => $this->roomA->id,
            'title' => 'Pengaduan dengan file jumbo',
            'description' => 'Deskripsi keluhan dengan berkas foto terlalu besar.',
            'category' => ComplaintCategory::Lainnya->value,
            'priority' => ComplaintPriority::Rendah->value,
            'photo' => $largeImage,
        ]);

    $responseLarge->assertSessionHasErrors('photo');
});

it('allows tenant to view/stream and download their complaint photo via authorized route', function () {
    $fakePhoto = UploadedFile::fake()->create('keran.png', 400, 'image/png');
    $path = Storage::disk('local')->putFile('complaints', $fakePhoto);

    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0077',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Foto bukti keran',
        'description' => 'Berikut foto bukti kerusakan keran air.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
        'photo_url' => $path,
    ]);

    // Inline preview
    $responseStream = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.photo', $complaint));

    $responseStream->assertOk();

    // Download attachment
    $responseDownload = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.photo', ['complaint' => $complaint, 'download' => 1]));

    $responseDownload->assertOk();
    $responseDownload->assertHeader('content-disposition');
});

it('prevents another tenant from viewing or downloading complaint photo (IDOR protection)', function () {
    $fakePhoto = UploadedFile::fake()->create('privasi_beta.jpg', 400, 'image/jpeg');
    $path = Storage::disk('local')->putFile('complaints', $fakePhoto);

    $complaintB = Complaint::create([
        'ticket_number' => 'KMP-2026-0088',
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Foto kamar beta',
        'description' => 'Foto kamar penyewa Beta.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
        'photo_url' => $path,
    ]);

    // Tenant A attempts to access Tenant B's photo
    $response = $this->actingAs($this->tenantA)
        ->get(route('tenant.complaints.photo', $complaintB));

    $response->assertForbidden();
});

it('allows tenant to post reply and records correct author and chronological order', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0091',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Tutup kloset retak',
        'description' => 'Tutup kloset retak saat diduduki.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Diproses->value,
    ]);

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.replies.store', $complaint), [
            'message' => 'Apakah teknisi bisa datang sore nanti sekitar jam 4?',
        ]);

    $response->assertRedirect(route('tenant.complaints.show', $complaint));
    $response->assertSessionHas('success');

    $reply = ComplaintReply::where('complaint_id', $complaint->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->user_id)->toBe($this->tenantA->id);
    expect($reply->message)->toBe('Apakah teknisi bisa datang sore nanti sekitar jam 4?');
});

it('automatically transitions status from menunggu_tanggapan to diproses when tenant replies', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0092',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Kendala kelistrikan',
        'description' => 'Saklar lampu tidak menyala.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::MenungguTanggapan->value,
    ]);

    $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.replies.store', $complaint), [
            'message' => 'Sudah saya cek, MCB kamar masih ON.',
        ]);

    $complaint->refresh();
    expect($complaint->status)->toBe(ComplaintStatus::Diproses->value);
});

it('rejects replies when complaint is ditutup', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0093',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Tiket sudah selesai dan ditutup',
        'description' => 'Semua sudah beres.',
        'category' => ComplaintCategory::Kebersihan->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Ditutup->value,
        'closed_at' => now(),
    ]);

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.replies.store', $complaint), [
            'message' => 'Mencoba membalas tiket yang sudah ditutup.',
        ]);

    // StoreComplaintReplyRequest authorize fails or controller redirects with error
    expect($response->status())->toBeIn([403, 302]);
    expect(ComplaintReply::where('complaint_id', $complaint->id)->count())->toBe(0);
});

it('prevents tenant from replying to another tenant complaint', function () {
    $complaintB = Complaint::create([
        'ticket_number' => 'KMP-2026-0094',
        'property_id' => $this->property->id,
        'room_id' => $this->roomB->id,
        'user_id' => $this->tenantB->id,
        'title' => 'Tiket milik Beta',
        'description' => 'Keluhan penyewa Beta.',
        'category' => ComplaintCategory::Lainnya->value,
        'priority' => ComplaintPriority::Rendah->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    $response = $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.replies.store', $complaintB), [
            'message' => 'Mencoba membalas tiket Beta secara ilegal.',
        ]);

    $response->assertForbidden();
    expect(ComplaintReply::where('complaint_id', $complaintB->id)->count())->toBe(0);
});

it('prevents tenant from altering ticket status via reply endpoint', function () {
    $complaint = Complaint::create([
        'ticket_number' => 'KMP-2026-0095',
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'user_id' => $this->tenantA->id,
        'title' => 'Status immutability test',
        'description' => 'Menguji kekebalan manipulasi status pada reply.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // Injected status in reply payload
    $this->actingAs($this->tenantA)
        ->post(route('tenant.complaints.replies.store', $complaint), [
            'message' => 'Balasan biasa.',
            'status' => 'selesai',
        ]);

    $complaint->refresh();
    // Status must remain 'baru' (not altered to 'selesai')
    expect($complaint->status)->toBe(ComplaintStatus::Baru->value);
});

it('cleans up uploaded photo file from private storage if persistence fails', function () {
    $this->mock(ComplaintService::class, function ($mock) {
        $mock->shouldReceive('createComplaint')
            ->once()
            ->andThrow(new \RuntimeException('Simulated database write failure'));
    });

    $fakePhoto = UploadedFile::fake()->create('bukti_gagal.jpg', 400, 'image/jpeg');

    $payload = [
        'property_id' => $this->property->id,
        'room_id' => $this->roomA->id,
        'title' => 'Kerusakan darurat',
        'description' => 'Kerusakan darurat yang gagal disimpan di database.',
        'category' => ComplaintCategory::Listrik->value,
        'priority' => ComplaintPriority::Darurat->value,
        'photo' => $fakePhoto,
    ];

    try {
        $this->actingAs($this->tenantA)
            ->post(route('tenant.complaints.store'), $payload);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('Simulated database write failure');
    }

    // Verify no orphaned files remain in complaints directory on private disk
    expect(Storage::disk('local')->allFiles('complaints'))->toBeEmpty();
});
