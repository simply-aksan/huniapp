<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\PropertyRuleCategory;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Property;
use App\Models\PropertyRule;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\HuniAiClient;
use App\Services\BillService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    Config::set('services.ai.base_url', 'http://127.0.0.1:8001');
    Config::set('services.ai.secret', 'phase12_test_secret_32chars_long!');
    Config::set('services.ai.timeout', 2);

    // Primary Owner Alpha
    $this->ownerAlpha = User::create([
        'name' => 'Pak Bambang Owner',
        'email' => 'owner_bambang_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // Unrelated Owner Beta (for IDOR and isolation testing)
    $this->ownerBeta = User::create([
        'name' => 'Ibu Ratna Owner',
        'email' => 'owner_ratna_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // Primary Tenant Alpha
    $this->tenantAlpha = User::create([
        'name' => 'Agus Penyewa',
        'email' => 'tenant_agus_'.Str::random(8).'@huniapp.test',
        'phone' => '0814'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Unrelated Tenant Beta (for IDOR and isolation testing)
    $this->tenantBeta = User::create([
        'name' => 'Dewi Penyewa',
        'email' => 'tenant_dewi_'.Str::random(8).'@huniapp.test',
        'phone' => '0815'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Flow 1: Owner Creates Property and Room
|--------------------------------------------------------------------------
*/
test('E2E Flow 1: Owner creates property and room with proper validation and ownership scoping', function () {
    $propData = [
        'name' => 'Kos Griya Sejahtera',
        'slug' => 'kos-griya-sejahtera-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Kaliurang KM 7.5 No. 45',
        'city' => 'Sleman',
        'district' => 'Ngaglik',
        'postal_code' => '55581',
        'status' => 'aktif',
    ];

    $propResponse = $this->actingAs($this->ownerAlpha)->post(route('owner.properties.store'), $propData);
    $propResponse->assertRedirect();

    $property = Property::where('slug', $propData['slug'])->firstOrFail();
    expect($property->owner_id)->toBe($this->ownerAlpha->id)
        ->and($property->name)->toBe('Kos Griya Sejahtera');

    $roomData = [
        'room_number' => 'GS-101',
        'floor' => 1,
        'type' => 'Deluxe',
        'price' => 1800000,
        'deposit' => 500000,
        'capacity' => 1,
        'status' => 'kosong',
        'description' => 'Kamar mandi dalam, AC, meja belajar.',
    ];

    $roomResponse = $this->actingAs($this->ownerAlpha)->post(route('owner.properties.rooms.store', $property), $roomData);
    $roomResponse->assertRedirect();

    $room = Room::where('property_id', $property->id)->where('room_number', 'GS-101')->firstOrFail();
    expect((float) $room->price)->toBe(1800000.0)
        ->and((int) $room->capacity)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Flow 2 & 3: Invitation, Contract Activation, Documents & Automatic Idempotent Invoicing
|--------------------------------------------------------------------------
*/
test('E2E Flow 2 & 3: Owner invites tenant, contract is activated with documents, and initial invoices are generated idempotently', function () {
    $property = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Griya Prima',
        'slug' => 'kos-griya-prima-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Affandi No. 10',
        'city' => 'Sleman',
        'district' => 'Depok',
        'postal_code' => '55281',
        'status' => 'aktif',
    ]);

    $room = Room::create([
        'property_id' => $property->id,
        'room_number' => 'GP-101',
        'floor' => 1,
        'type' => 'Standard',
        'price' => 1500000,
        'deposit' => 500000,
        'capacity' => 1,
        'status' => 'kosong',
    ]);

    // 1. Owner invites Tenant via official invitation flow
    $inviteRes = $this->actingAs($this->ownerAlpha)->post(route('owner.invitations.store'), [
        'property_id' => $property->id,
        'room_id' => $room->id,
        'name' => $this->tenantAlpha->name,
        'email' => $this->tenantAlpha->email,
        'phone' => $this->tenantAlpha->phone,
    ]);
    $inviteRes->assertRedirect();

    $invitation = Invitation::where('email', $this->tenantAlpha->email)->firstOrFail();
    expect($invitation->status)->toBe('aktif');

    // 2. Tenant accepts invitation
    $acceptRes = $this->actingAs($this->tenantAlpha)->post(route('invitations.accept', $invitation->token));
    $acceptRes->assertRedirect();

    $invitation->refresh();
    expect($invitation->status)->toBe('diterima');

    // 3. Owner creates active contract for the accepted tenant
    $startDate = Carbon::today()->startOfMonth();
    $endDate = $startDate->copy()->addMonths(6)->subDay();

    $contractData = [
        'property_id' => $property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 1500000,
        'deposit_amount' => 500000,
        'deposit_status' => 'belum_dibayar',
        'status' => ContractStatus::Aktif->value,
    ];

    $response = $this->actingAs($this->ownerAlpha)->post(route('owner.contracts.store'), $contractData);
    $response->assertRedirect();

    $contract = Contract::where('user_id', $this->tenantAlpha->id)->firstOrFail();
    expect($contract->status)->toBe(ContractStatus::Aktif->value);

    // Verify room status was transitioned to terisi
    $room->refresh();
    expect($room->status)->toBe('terisi');

    // Flow 3: Verify initial rent bill and deposit bill were generated
    $rentBill = Bill::where('contract_id', $contract->id)
        ->where('type', BillType::Sewa->value)
        ->first();
    $depositBill = Bill::where('contract_id', $contract->id)
        ->where('type', BillType::Deposit->value)
        ->first();

    expect($rentBill)->not->toBeNull()
        ->and((float) $rentBill->total_amount)->toBe(1500000.0)
        ->and($rentBill->status)->toBe(BillStatus::MenungguPembayaran->value)
        ->and($depositBill)->not->toBeNull()
        ->and((float) $depositBill->total_amount)->toBe(500000.0);

    // Verify Idempotency: Calling generateInitialInvoices again produces no duplicate bills
    $billService = app(BillService::class);
    $duplicateAttempt = $billService->generateInitialInvoices($contract);
    expect($duplicateAttempt['rent']->id)->toBe($rentBill->id)
        ->and($duplicateAttempt['deposit']->id)->toBe($depositBill->id);

    expect(Bill::where('contract_id', $contract->id)->count())->toBe(2);

    // Test Contract Document Upload
    $dummyPdf = UploadedFile::fake()->create('surat_perjanjian_sewa.pdf', 300, 'application/pdf');
    $uploadRes = $this->actingAs($this->ownerAlpha)->post(
        route('owner.contracts.documents.upload', $contract),
        [
            'document' => $dummyPdf,
            'title' => 'Surat Perjanjian Sewa Asli',
        ]
    );
    $uploadRes->assertRedirect();

    $doc = $contract->documents()->first();
    expect($doc)->not->toBeNull()
        ->and(Storage::disk('local')->exists($doc->file_path))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Flow 4, 5 & 6: Tenant Submits Payment, Owner Verifies/Rejects & Updates Balance
|--------------------------------------------------------------------------
*/
test('E2E Flow 4, 5 & 6: Tenant submits payment proofs, Owner verifies partial and full payments, and handles rejection', function () {
    $property = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Mahakam',
        'slug' => 'kos-mahakam-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Mahakam No. 8',
        'city' => 'Jakarta Selatan',
        'status' => 'aktif',
    ]);

    $room = Room::create([
        'property_id' => $property->id,
        'room_number' => 'M-201',
        'floor' => 2,
        'type' => 'VIP',
        'price' => 2000000,
        'deposit' => 500000,
        'capacity' => 1,
        'status' => 'terisi',
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-MHK-001',
        'property_id' => $property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->startOfMonth(),
        'end_date' => Carbon::today()->startOfMonth()->addMonths(6),
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    $bill = Bill::create([
        'bill_number' => 'INV-202610-0099',
        'contract_id' => $contract->id,
        'user_id' => $this->tenantAlpha->id,
        'room_id' => $room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // 1. Tenant submits Partial Payment 1: 1,200,000 IDR
    $fakeProof1 = UploadedFile::fake()->create('transfer_part1.jpg', 150, 'image/jpeg');
    $payRes1 = $this->actingAs($this->tenantAlpha)->post(
        route('tenant.bills.payments.store', $bill),
        [
            'amount' => 1200000,
            'payment_method' => 'Transfer BCA',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pembayaran termin pertama',
            'proofs' => [$fakeProof1],
        ]
    );
    $payRes1->assertRedirect();

    $payment1 = Payment::where('bill_id', $bill->id)->firstOrFail();
    expect($payment1->status)->toBe(PaymentStatus::MenungguVerifikasi->value)
        ->and((float) $payment1->amount)->toBe(1200000.0);

    // 2. Owner approves Payment 1
    $approveRes1 = $this->actingAs($this->ownerAlpha)->post(route('owner.payments.approve', $payment1));
    $approveRes1->assertRedirect();

    $payment1->refresh();
    $bill->refresh();

    expect($payment1->status)->toBe(PaymentStatus::Berhasil->value)
        ->and((float) $bill->paid_amount)->toBe(1200000.0)
        ->and($bill->status)->toBe(BillStatus::Sebagian->value);

    // 3. Tenant submits Payment 2 (invalid / wrong proof) to test Rejection
    $fakeProof2 = UploadedFile::fake()->create('invalid_slip.jpg', 150, 'image/jpeg');
    $this->actingAs($this->tenantAlpha)->post(
        route('tenant.bills.payments.store', $bill),
        [
            'amount' => 800000,
            'payment_method' => 'Transfer Mandiri',
            'payment_date' => now()->toDateString(),
            'notes' => 'Slip salah unggah',
            'proofs' => [$fakeProof2],
        ]
    );

    $payment2 = Payment::where('bill_id', $bill->id)
        ->where('id', '!=', $payment1->id)
        ->firstOrFail();

    $rejectRes = $this->actingAs($this->ownerAlpha)->post(
        route('owner.payments.reject', $payment2),
        ['rejection_reason' => 'Bukti mutasi rekening tidak terbaca / buram.']
    );
    $rejectRes->assertRedirect();

    $payment2->refresh();
    $bill->refresh();

    expect($payment2->status)->toBe(PaymentStatus::Ditolak->value)
        ->and($payment2->rejection_reason)->toBe('Bukti mutasi rekening tidak terbaca / buram.')
        ->and((float) $bill->paid_amount)->toBe(1200000.0) // Unchanged
        ->and($bill->status)->toBe(BillStatus::Sebagian->value);

    // 4. Tenant resubmits correct remaining Payment 3: 800,000 IDR
    $fakeProof3 = UploadedFile::fake()->create('transfer_final.jpg', 150, 'image/jpeg');
    $this->actingAs($this->tenantAlpha)->post(
        route('tenant.bills.payments.store', $bill),
        [
            'amount' => 800000,
            'payment_method' => 'Transfer BCA',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pelunasan sisa tagihan',
            'proofs' => [$fakeProof3],
        ]
    );

    $payment3 = Payment::where('bill_id', $bill->id)
        ->whereNotIn('id', [$payment1->id, $payment2->id])
        ->firstOrFail();

    $this->actingAs($this->ownerAlpha)->post(route('owner.payments.approve', $payment3));

    $bill->refresh();
    expect((float) $bill->paid_amount)->toBe(2000000.0)
        ->and($bill->status)->toBe(BillStatus::Lunas->value);
});

/*
|--------------------------------------------------------------------------
| Flow 7: Notification Center Lifecycle
|--------------------------------------------------------------------------
*/
test('E2E Flow 7: System notifications are generated and accessible with mark-read features', function () {
    // Generate a dummy unread notification for Tenant
    $this->tenantAlpha->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\PaymentApprovedNotification',
        'data' => [
            'title' => 'Pembayaran Diterima',
            'message' => 'Pembayaran tagihan kos Anda telah diverifikasi oleh pemilik kos.',
            'url' => '/tenant/bills',
        ],
        'read_at' => null,
    ]);

    // Check notifications index
    $res = $this->actingAs($this->tenantAlpha)->get(route('notifications.index'));
    $res->assertOk();
    $res->assertSee('Pembayaran Diterima');

    // Mark single notification as read via JSON endpoint
    $notification = $this->tenantAlpha->unreadNotifications()->firstOrFail();
    $readRes = $this->actingAs($this->tenantAlpha)->postJson(route('notifications.read', $notification->id));
    $readRes->assertOk();
    $readRes->assertJsonPath('success', true);

    expect($this->tenantAlpha->unreadNotifications()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Flow 8: Complaint Management Lifecycle
|--------------------------------------------------------------------------
*/
test('E2E Flow 8: Tenant submits complaint with photo, Owner replies and resolves, and complaint is closed', function () {
    $property = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Alamanda',
        'slug' => 'kos-alamanda-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Alamanda No. 12',
        'city' => 'Yogyakarta',
        'status' => 'aktif',
    ]);

    $room = Room::create([
        'property_id' => $property->id,
        'room_number' => 'AL-05',
        'floor' => 1,
        'type' => 'Deluxe',
        'price' => 1700000,
        'deposit' => 500000,
        'capacity' => 1,
        'status' => 'terisi',
    ]);

    Contract::create([
        'contract_number' => 'CTR-ALM-001',
        'property_id' => $property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->startOfMonth(),
        'end_date' => Carbon::today()->startOfMonth()->addMonths(6),
        'duration_months' => 6,
        'monthly_rent' => 1700000,
        'total_amount' => 10200000,
        'deposit_amount' => 500000,
        'status' => 'aktif',
    ]);

    // 1. Tenant files complaint
    $photo = UploadedFile::fake()->create('air_bocor.jpg', 200, 'image/jpeg');
    $complaintData = [
        'property_id' => $property->id,
        'room_id' => $room->id,
        'title' => 'Pipa wastafel bocor',
        'description' => 'Air terus menetes di bawah wastafel dan membasahi lantai kamar mandi.',
        'category' => ComplaintCategory::AirSanitasi->value,
        'priority' => ComplaintPriority::Sedang->value,
        'photo' => $photo,
    ];

    $storeRes = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.store'), $complaintData);
    $storeRes->assertRedirect();

    $complaint = Complaint::where('user_id', $this->tenantAlpha->id)->firstOrFail();
    expect($complaint->status)->toBe(ComplaintStatus::Baru->value)
        ->and($complaint->photo_url)->not->toBeNull()
        ->and(Storage::disk('local')->exists($complaint->photo_url))->toBeTrue();

    // 2. Owner responds with reply
    $replyRes = $this->actingAs($this->ownerAlpha)->post(
        route('owner.complaints.replies.store', $complaint),
        ['message' => 'Baik, teknisi akan datang memeriksa pukul 14:00 siang ini.']
    );
    $replyRes->assertRedirect();
    expect($complaint->replies()->count())->toBe(1);

    // 3. Owner updates status to Diproses then Selesai
    $this->actingAs($this->ownerAlpha)->post(
        route('owner.complaints.status.update', $complaint),
        ['status' => ComplaintStatus::Diproses->value, 'note' => 'Teknisi sedang bekerja.']
    );
    $complaint->refresh();
    expect($complaint->status)->toBe(ComplaintStatus::Diproses->value);

    $this->actingAs($this->ownerAlpha)->post(
        route('owner.complaints.status.update', $complaint),
        ['status' => ComplaintStatus::Selesai->value, 'note' => 'Pipa wastafel telah diganti baru.']
    );
    $complaint->refresh();
    expect($complaint->status)->toBe(ComplaintStatus::Selesai->value);

    // 4. Tenant closes the ticket
    $closeRes = $this->actingAs($this->tenantAlpha)->post(route('tenant.complaints.close', $complaint));
    $closeRes->assertRedirect();
    $complaint->refresh();
    expect($complaint->status)->toBe(ComplaintStatus::Ditutup->value);
});

/*
|--------------------------------------------------------------------------
| Flow 9: Analytics & Financial Reports Export
|--------------------------------------------------------------------------
*/
test('E2E Flow 9: Analytics and Financial Reports reflect verified payments and stream CSV', function () {
    $property = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Melati Raya',
        'slug' => 'kos-melati-raya-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Melati Raya No. 1',
        'city' => 'Jakarta Selatan',
        'status' => 'aktif',
    ]);

    $room = Room::create([
        'property_id' => $property->id,
        'room_number' => 'MR-01',
        'floor' => 1,
        'type' => 'Standard',
        'price' => 1000000,
        'deposit' => 300000,
        'capacity' => 1,
        'status' => 'terisi',
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-MR-001',
        'property_id' => $property->id,
        'room_id' => $room->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->startOfMonth(),
        'end_date' => Carbon::today()->startOfMonth()->addMonths(6),
        'duration_months' => 6,
        'monthly_rent' => 1000000,
        'total_amount' => 6000000,
        'deposit_amount' => 300000,
        'status' => 'aktif',
    ]);

    $bill = Bill::create([
        'bill_number' => 'INV-202610-0999',
        'contract_id' => $contract->id,
        'user_id' => $this->tenantAlpha->id,
        'room_id' => $room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 1000000,
        'paid_amount' => 1000000,
        'status' => BillStatus::Lunas->value,
    ]);

    Payment::create([
        'payment_number' => 'PAY-202610-0999',
        'bill_id' => $bill->id,
        'user_id' => $this->tenantAlpha->id,
        'amount' => 1000000,
        'payment_method' => 'Transfer BCA',
        'payment_date' => now()->toDateString(),
        'status' => PaymentStatus::Berhasil->value,
        'verified_by' => $this->ownerAlpha->id,
        'verified_at' => now(),
    ]);

    // 1. Analytics view
    $analyticsRes = $this->actingAs($this->ownerAlpha)->get(route('owner.analytics.index'));
    $analyticsRes->assertOk();

    // 2. Financial Reports Print View
    $printRes = $this->actingAs($this->ownerAlpha)->get(route('owner.reports.print'));
    $printRes->assertOk();
    $printRes->assertSee('Kos Melati Raya');

    // 3. Financial Reports CSV Export
    $exportRes = $this->actingAs($this->ownerAlpha)->get(route('owner.reports.export'));
    $exportRes->assertOk();
    expect($exportRes->headers->get('content-type'))->toContain('text/csv');
});

/*
|--------------------------------------------------------------------------
| Flow 10: Knowledge Base Scoping & Chatbot Property Isolation
|--------------------------------------------------------------------------
*/
test('E2E Flow 10: Knowledge base rules and chatbot responses are strictly scoped to the authorized property', function () {
    // Property Alpha with specific rule
    $propertyAlpha = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Sakura Alpha',
        'slug' => 'kos-sakura-alpha-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Sakura No. 3',
        'city' => 'Yogyakarta',
        'status' => 'aktif',
    ]);

    $roomAlpha = Room::create([
        'property_id' => $propertyAlpha->id,
        'room_number' => 'SK-01',
        'floor' => 1,
        'type' => 'Standard',
        'price' => 1000000,
        'capacity' => 1,
        'status' => 'terisi',
    ]);

    Contract::create([
        'contract_number' => 'CTR-SK-001',
        'property_id' => $propertyAlpha->id,
        'room_id' => $roomAlpha->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->startOfMonth(),
        'end_date' => Carbon::today()->startOfMonth()->addMonths(6),
        'duration_months' => 6,
        'monthly_rent' => 1000000,
        'total_amount' => 6000000,
        'status' => 'aktif',
    ]);

    // Add Rule to Property Alpha
    PropertyRule::create([
        'property_id' => $propertyAlpha->id,
        'category' => PropertyRuleCategory::JamMalam->value,
        'title' => 'Jam Malam Kos Sakura',
        'content' => 'Gerbang utama dikunci pukul 23:00 WIB.',
        'is_active' => true,
    ]);

    // Fake FastAPI endpoints
    Http::fake([
        '*/api/v1/rag/properties/*/status' => Http::response([
            'property_id' => $propertyAlpha->id,
            'is_indexed' => true,
            'active_documents_count' => 1,
            'last_indexed_at' => now()->toIso8601String(),
        ], 200),
        '*/api/v1/rag/query' => Http::response([
            'status' => 'ok',
            'property_id' => $propertyAlpha->id,
            'question' => 'Jam berapa gerbang dikunci?',
            'retrieval_status' => 'grounded_retrieval',
            'is_fallback' => false,
            'answer' => 'Pintu gerbang utama Kos Sakura dikunci pukul 23:00 WIB setiap hari.',
            'sources' => [
                [
                    'id' => '1',
                    'source_type' => 'rule',
                    'title' => 'Jam Malam Kos Sakura',
                    'content' => 'Gerbang utama dikunci pukul 23:00 WIB.',
                    'category' => 'jam_malam',
                    'relevance_score' => 0.95,
                ],
            ],
            'active_documents_count' => 1,
        ], 200),
    ]);

    $chatRes = $this->actingAs($this->tenantAlpha)->postJson(route('tenant.chat.ask'), [
        'question' => 'Jam berapa gerbang dikunci?',
    ]);

    $chatRes->assertOk();
    $chatRes->assertJsonPath('answer', 'Pintu gerbang utama Kos Sakura dikunci pukul 23:00 WIB setiap hari.');

    // Verify chat was logged with correct property_id
    $this->assertDatabaseHas('ai_chat_logs', [
        'user_id' => $this->tenantAlpha->id,
        'property_id' => $propertyAlpha->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Flow 11: Delinquency Risk Engine Graceful Degradation on FastAPI Failure
|--------------------------------------------------------------------------
*/
test('E2E Flow 11: Delinquency risk evaluation degrades gracefully and does not break billing when FastAPI fails', function () {
    // Simulate FastAPI failure with ConnectionException
    Http::fake([
        'http://127.0.0.1:8001/*' => function () {
            throw new ConnectionException('Failed to connect to FastAPI service.');
        },
    ]);

    $client = app(HuniAiClient::class);
    $health = $client->healthCheck();

    expect($health['success'])->toBeFalse();

    // Risk assessment should return safe error structure rather than uncaught exception
    $riskResult = $client->evaluateRisk([
        'bill_id' => 9999,
        'property_id' => 1,
        'tenant_id' => 1,
        'total_amount' => 1500000,
        'days_overdue' => 2,
        'unpaid_bills_count' => 1,
        'late_payment_history_count' => 0,
    ]);

    // Verify it handled the connection error safely
    expect($riskResult['success'])->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Flow 12: Security & Authorization Regression (Cross-Tenant & Cross-Owner IDOR)
|--------------------------------------------------------------------------
*/
test('Security Regression: Cross-tenant and cross-owner IDOR access is strictly blocked with 403 or 404', function () {
    $propertyAlpha = Property::create([
        'owner_id' => $this->ownerAlpha->id,
        'name' => 'Kos Alpha Exclusive',
        'slug' => 'kos-alpha-excl-'.Str::random(6),
        'type' => 'campur',
        'address' => 'Jl. Eksklusif No. 1',
        'city' => 'Jakarta',
        'status' => 'aktif',
    ]);

    $roomAlpha = Room::create([
        'property_id' => $propertyAlpha->id,
        'room_number' => 'EX-01',
        'floor' => 1,
        'type' => 'VIP',
        'price' => 2500000,
        'capacity' => 1,
        'status' => 'terisi',
    ]);

    $contractAlpha = Contract::create([
        'contract_number' => 'CTR-SEC-001',
        'property_id' => $propertyAlpha->id,
        'room_id' => $roomAlpha->id,
        'user_id' => $this->tenantAlpha->id,
        'start_date' => Carbon::today()->startOfMonth(),
        'end_date' => Carbon::today()->startOfMonth()->addMonths(6),
        'duration_months' => 6,
        'monthly_rent' => 2500000,
        'total_amount' => 15000000,
        'status' => 'aktif',
    ]);

    $billAlpha = Bill::create([
        'bill_number' => 'INV-SEC-001',
        'contract_id' => $contractAlpha->id,
        'user_id' => $this->tenantAlpha->id,
        'room_id' => $roomAlpha->id,
        'type' => BillType::Sewa->value,
        'billing_period' => '2026-10',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2500000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $paymentAlpha = Payment::create([
        'payment_number' => 'PAY-SEC-001',
        'bill_id' => $billAlpha->id,
        'user_id' => $this->tenantAlpha->id,
        'amount' => 2500000,
        'payment_method' => 'Transfer BCA',
        'payment_date' => now()->toDateString(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    Storage::disk('local')->put('proofs/secret_proof.jpg', 'fake-image-binary');
    $proof = PaymentProof::create([
        'payment_id' => $paymentAlpha->id,
        'file_name' => 'secret_proof.jpg',
        'file_path' => 'proofs/secret_proof.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1234,
    ]);

    $complaintAlpha = Complaint::create([
        'ticket_number' => 'KMP-SEC-001',
        'property_id' => $propertyAlpha->id,
        'room_id' => $roomAlpha->id,
        'user_id' => $this->tenantAlpha->id,
        'title' => 'Privasi Kamar',
        'description' => 'Deskripsi rahasia keluhan penghuni Alpha.',
        'category' => ComplaintCategory::Keamanan->value,
        'priority' => ComplaintPriority::Tinggi->value,
        'status' => ComplaintStatus::Baru->value,
    ]);

    // 1. Owner Beta attempts to access Owner Alpha's property
    $this->actingAs($this->ownerBeta)->get(route('owner.properties.show', $propertyAlpha))->assertForbidden();

    // 2. Owner Beta attempts to approve Tenant Alpha's payment
    $this->actingAs($this->ownerBeta)->post(route('owner.payments.approve', $paymentAlpha))->assertForbidden();

    // 3. Owner Beta attempts to view Complaint Alpha
    $this->actingAs($this->ownerBeta)->get(route('owner.complaints.show', $complaintAlpha))->assertForbidden();

    // 4. Tenant Beta attempts to view Tenant Alpha's bill
    $this->actingAs($this->tenantBeta)->get(route('tenant.bills.show', $billAlpha))->assertForbidden();

    // 5. Tenant Beta attempts to pay Tenant Alpha's bill (Must be rejected with error)
    $payAttempt = $this->actingAs($this->tenantBeta)->post(route('tenant.bills.payments.store', $billAlpha), [
        'amount' => 1000000,
        'payment_method' => 'Transfer',
        'payment_date' => now()->toDateString(),
        'proofs' => [UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg')],
    ]);
    $payAttempt->assertSessionHasErrors(['amount' => 'Anda tidak memiliki hak akses terhadap tagihan ini.']);
    expect(Payment::where('bill_id', $billAlpha->id)->where('user_id', $this->tenantBeta->id)->exists())->toBeFalse();

    // 6. Tenant Beta attempts to download Tenant Alpha's payment proof
    $this->actingAs($this->tenantBeta)->get(route('payments.proofs.download', [$paymentAlpha, $proof]))->assertForbidden();

    // 7. Unauthenticated user cannot access private payment proof
    Auth::logout();
    $this->get(route('payments.proofs.download', [$paymentAlpha, $proof]))->assertRedirect(route('login'));
});
