<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BillDueReminderNotification;
use App\Notifications\ComplaintReplyReceivedNotification;
use App\Notifications\ComplaintStatusUpdatedNotification;
use App\Notifications\ContractActivatedNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\NewComplaintSubmittedNotification;
use App\Notifications\NewPaymentSubmittedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Services\BillService;
use App\Services\ComplaintService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    // Setup Owner
    $this->owner = User::create([
        'name' => 'Owner Notification Test',
        'email' => 'owner_notif_'.Str::random(8).'@huniapp.test',
        'phone' => '0812'.rand(10000000, 99999999),
        'role' => UserRole::Owner,
        'password' => Hash::make('password'),
    ]);

    // Setup Property
    $this->property = Property::create([
        'owner_id' => $this->owner->id,
        'name' => 'Kos Notifikasi '.Str::random(5),
        'slug' => 'kos-notif-'.Str::random(8),
        'type' => 'campur',
        'address' => 'Jl. Notifikasi No. 1',
        'city' => 'Jakarta Selatan',
        'district' => 'Kuningan',
        'postal_code' => '12940',
        'total_rooms' => 2,
        'status' => 'aktif',
    ]);

    // Setup Room
    $this->room = Room::create([
        'property_id' => $this->property->id,
        'room_number' => 'N-101',
        'floor' => 1,
        'type' => 'standar',
        'price' => 2000000,
        'status' => 'terisi',
    ]);

    // Setup Tenant
    $this->tenant = User::create([
        'name' => 'Tenant Notification Test',
        'email' => 'tenant_notif_'.Str::random(8).'@huniapp.test',
        'phone' => '0813'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);

    // Setup Contract
    $this->contract = Contract::create([
        'contract_number' => 'CTR-TEST-'.Str::random(6),
        'property_id' => $this->property->id,
        'room_id' => $this->room->id,
        'user_id' => $this->tenant->id,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->endOfMonth()->toDateString(),
        'duration_months' => 6,
        'monthly_rent' => 2000000,
        'total_amount' => 12000000,
        'deposit_amount' => 500000,
        'deposit_status' => 'belum_dibayar',
        'payment_method' => 'transfer_bank',
        'status' => 'aktif',
    ]);

    // Setup Unrelated User
    $this->otherUser = User::create([
        'name' => 'Other User Test',
        'email' => 'other_notif_'.Str::random(8).'@huniapp.test',
        'phone' => '0819'.rand(10000000, 99999999),
        'role' => UserRole::Tenant,
        'password' => Hash::make('password'),
    ]);
});

test('1. InvoiceCreatedNotification is sent to tenant upon bill creation', function () {
    Notification::fake();

    $billService = app(BillService::class);
    $bill = $billService->createInvoice([
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
    ], [
        ['description' => 'Sewa Bulanan', 'amount' => 2000000, 'quantity' => 1],
    ]);

    app(NotificationService::class)->notifyInvoiceCreated($bill);

    Notification::assertSentTo($this->tenant, InvoiceCreatedNotification::class, function ($notification) use ($bill) {
        return $notification->bill->id === $bill->id;
    });

    Notification::assertNotSentTo($this->otherUser, InvoiceCreatedNotification::class);
});

test('2. NewPaymentSubmittedNotification is sent to owner when tenant submits payment proof', function () {
    Notification::fake();
    Storage::fake('local');

    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $file = UploadedFile::fake()->create('struk.jpg', 200, 'image/jpeg');

    $response = $this->actingAs($this->tenant)->post(route('tenant.bills.payments.store', $bill), [
        'amount' => 2000000,
        'payment_method' => 'transfer_bank',
        'payment_date' => now()->toDateString(),
        'reference_number' => 'TRX-123456',
        'proofs' => [$file],
    ]);

    $response->assertRedirect(route('tenant.bills.show', $bill));

    Notification::assertSentTo($this->owner, NewPaymentSubmittedNotification::class, function ($notification) use ($bill) {
        return $notification->payment->bill_id === $bill->id;
    });

    Notification::assertNotSentTo($this->otherUser, NewPaymentSubmittedNotification::class);
});

test('3. PaymentVerifiedNotification is sent to tenant upon owner verification', function () {
    Notification::fake();

    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-TEST-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_method' => 'transfer_bank',
        'payment_date' => now()->toDateString(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    $billService = app(BillService::class);
    $billService->approvePayment($payment, $this->owner);

    Notification::assertSentTo($this->tenant, PaymentVerifiedNotification::class, function ($notification) use ($payment) {
        return $notification->payment->id === $payment->id;
    });

    Notification::assertNotSentTo($this->otherUser, PaymentVerifiedNotification::class);
});

test('4. PaymentRejectedNotification is sent to tenant with rejection reason', function () {
    Notification::fake();

    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $payment = Payment::create([
        'payment_number' => 'PAY-TEST-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_method' => 'transfer_bank',
        'payment_date' => now()->toDateString(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    $billService = app(BillService::class);
    $billService->rejectPayment($payment, $this->owner, 'Bukti transfer tidak terbaca jelas');

    Notification::assertSentTo($this->tenant, PaymentRejectedNotification::class, function ($notification) use ($payment) {
        return $notification->payment->id === $payment->id
            && $notification->payment->rejection_reason === 'Bukti transfer tidak terbaca jelas';
    });

    // Verify PaymentVerifiedNotification was NOT sent
    Notification::assertNotSentTo($this->tenant, PaymentVerifiedNotification::class);
});

test('5. Complaints dispatch NewComplaintSubmittedNotification and ComplaintReplyReceivedNotification to correct parties', function () {
    Notification::fake();

    $complaintService = app(ComplaintService::class);

    // 1. Tenant creates complaint -> Owner notified
    $complaint = $complaintService->createComplaint([
        'property_id' => $this->property->id,
        'room_id' => $this->room->id,
        'title' => 'AC Kurang Dingin',
        'description' => 'AC di kamar N-101 hanya mengeluarkan angin.',
        'category' => ComplaintCategory::FasilitasKamar->value,
        'priority' => ComplaintPriority::Sedang->value,
    ], $this->tenant);

    Notification::assertSentTo($this->owner, NewComplaintSubmittedNotification::class, function ($notification) use ($complaint) {
        return $notification->complaint->id === $complaint->id;
    });
    Notification::assertNotSentTo($this->tenant, NewComplaintSubmittedNotification::class);

    // 2. Owner replies -> Tenant notified
    $replyByOwner = $complaintService->addReply($complaint, $this->owner, 'Teknisi akan datang besok siang jam 14:00.');

    Notification::assertSentTo($this->tenant, ComplaintReplyReceivedNotification::class, function ($notification) use ($complaint) {
        return $notification->complaint->id === $complaint->id && $notification->author->id === $this->owner->id;
    });

    // 3. Tenant replies -> Owner notified
    $replyByTenant = $complaintService->addReply($complaint, $this->tenant, 'Baik pak, saya tunggu besok.');

    Notification::assertSentTo($this->owner, ComplaintReplyReceivedNotification::class, function ($notification) use ($complaint) {
        return $notification->complaint->id === $complaint->id && $notification->author->id === $this->tenant->id;
    });

    // 4. Owner updates status -> Tenant notified
    $complaintService->updateStatus($complaint, ComplaintStatus::Diproses, $this->owner);

    Notification::assertSentTo($this->tenant, ComplaintStatusUpdatedNotification::class);
});

test('6. ContractActivatedNotification is dispatched when contract is active', function () {
    Notification::fake();

    app(NotificationService::class)->notifyContractActivated($this->contract);

    Notification::assertSentTo($this->tenant, ContractActivatedNotification::class, function ($notification) {
        return $notification->contract->id === $this->contract->id;
    });
});

test('7. Notification Center index displays only the authenticated user notifications', function () {
    // Send database notifications to Owner and Tenant
    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));

    $payment = Payment::create([
        'payment_number' => 'PAY-TEST-'.Str::random(6),
        'bill_id' => $bill->id,
        'user_id' => $this->tenant->id,
        'amount' => 2000000,
        'payment_method' => 'transfer_bank',
        'payment_date' => now()->toDateString(),
        'status' => PaymentStatus::MenungguVerifikasi->value,
    ]);

    $this->owner->notify(new NewPaymentSubmittedNotification($payment));

    // Verify tenant sees their own notification
    $responseTenant = $this->actingAs($this->tenant)->get(route('notifications.index'));
    $responseTenant->assertOk();
    $responseTenant->assertSee('Tagihan Baru Diterbitkan');
    $responseTenant->assertDontSee('Pembayaran Baru Menunggu Verifikasi');

    // Verify owner sees their own notification
    $responseOwner = $this->actingAs($this->owner)->get(route('notifications.index'));
    $responseOwner->assertOk();
    $responseOwner->assertSee('Pembayaran Baru Menunggu Verifikasi');
    $responseOwner->assertDontSee('Tagihan Baru Diterbitkan');
});

test('8. User cannot mark another user notification as read', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));
    $tenantNotif = $this->tenant->notifications()->first();

    // Owner tries to mark Tenant's notification as read -> should fail with 404
    $response = $this->actingAs($this->owner)->post(route('notifications.read', $tenantNotif->id));
    $response->assertNotFound();

    // Notification remains unread
    expect($tenantNotif->fresh()->read())->toBeFalse();
});

test('9. markAsRead and markAllAsRead correctly update notification status', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-TEST-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));
    $this->tenant->notify(new BillDueReminderNotification($bill));

    expect($this->tenant->unreadNotifications()->count())->toBe(2);

    $firstNotif = $this->tenant->unreadNotifications()->first();

    // 1. Mark single notification as read
    $response = $this->actingAs($this->tenant)->post(route('notifications.read', $firstNotif->id));
    $response->assertRedirect(route('tenant.bills.show', $bill));

    expect($firstNotif->fresh()->read())->toBeTrue();
    expect($this->tenant->unreadNotifications()->count())->toBe(1);

    // 2. Mark all as read
    $responseAll = $this->actingAs($this->tenant)->post(route('notifications.readAll'));
    $responseAll->assertRedirect();

    expect($this->tenant->unreadNotifications()->count())->toBe(0);
});

test('10. markAsRead protects against open redirect attacks', function () {
    // Manually create a notification with malicious external URL
    $this->tenant->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => InvoiceCreatedNotification::class,
        'data' => [
            'title' => 'Malicious Notification',
            'message' => 'Phishing attempt',
            'url' => 'https://evil-phishing-site.com/login',
        ],
        'read_at' => null,
    ]);

    $notif = $this->tenant->notifications()->first();

    $response = $this->actingAs($this->tenant)->post(route('notifications.read', $notif->id));

    // Must NOT redirect to evil-phishing-site.com!
    $response->assertRedirect(route('notifications.index'));
});

test('11. Scheduled due reminders command is idempotent and respects H-3 and H+1 rules', function () {
    $today = Carbon::today();

    // 1. Bill due in 3 days (H-3)
    $upcomingBill = Bill::create([
        'bill_number' => 'INV-UPCOMING-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => $today->format('Y-m'),
        'issue_date' => $today->copy()->subDays(4)->toDateString(),
        'due_date' => $today->copy()->addDays(3)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // 2. Bill overdue by 1 day (H+1)
    $overdueBill = Bill::create([
        'bill_number' => 'INV-OVERDUE-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => $today->format('Y-m'),
        'issue_date' => $today->copy()->subDays(8)->toDateString(),
        'due_date' => $today->copy()->subDay()->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // 3. Paid bill that happens to have due_date in 3 days (MUST NOT BE NOTIFIED)
    $paidBill = Bill::create([
        'bill_number' => 'INV-PAID-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => $today->format('Y-m'),
        'issue_date' => $today->copy()->subDays(4)->toDateString(),
        'due_date' => $today->copy()->addDays(3)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 2000000,
        'status' => BillStatus::Lunas->value,
    ]);

    // Run command first time
    $this->artisan('bills:send-due-reminders')
        ->expectsOutputToContain('H-3 Pengingat: 1 terkirim')
        ->expectsOutputToContain('H+1 Tunggakan: 1 terkirim')
        ->assertSuccessful();

    // Verify database notifications created
    $notifsCountFirst = $this->tenant->notifications()->count();
    expect($notifsCountFirst)->toBe(2);

    // Run command second time (IDEMPOTENT CHECK)
    $this->artisan('bills:send-due-reminders')
        ->expectsOutputToContain('H-3 Pengingat: 0 terkirim, 1 dilewati')
        ->expectsOutputToContain('H+1 Tunggakan: 0 terkirim, 1 dilewati')
        ->assertSuccessful();

    // Total notifications should remain exactly 2, no spam!
    $notifsCountSecond = $this->tenant->notifications()->count();
    expect($notifsCountSecond)->toBe(2);
});

test('12. Guest is redirected to login when accessing notification routes', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->post(route('notifications.readAll'))->assertRedirect(route('login'));
    $this->post(route('notifications.read', 'fake-uuid'))->assertRedirect(route('login'));
});

test('13. Notification mail representation generates valid Indonesian text and action button', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-MAIL-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $notif = new InvoiceCreatedNotification($bill);
    $mail = $notif->toMail($this->tenant);

    expect($mail->subject)->toContain($bill->bill_number)
        ->and($mail->greeting)->toContain($this->tenant->name)
        ->and($mail->actionText)->toBe('Lihat & Bayar Tagihan')
        ->and($mail->actionUrl)->toBe(route('tenant.bills.show', $bill));
});

test('14. Mail failure does not abort or rollback database business transactions', function () {
    $service = new NotificationService;

    // Simulate an object that throws upon notify
    $throwingUser = new class extends User
    {
        public function notify($instance)
        {
            throw new \RuntimeException('SMTP Server Connection Timed Out');
        }
    };
    $throwingUser->id = 99999;

    $bill = Bill::create([
        'bill_number' => 'INV-FAIL-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    // Transaction succeeds even if notification fails
    $result = DB::transaction(function () use ($service, $throwingUser, $bill) {
        $bill->update(['notes' => 'Catatan sukses disimpan']);
        $service->sendSafely($throwingUser, new InvoiceCreatedNotification($bill));

        return true;
    });

    expect($result)->toBeTrue();
    expect($bill->fresh()->notes)->toBe('Catatan sukses disimpan');
});

test('15. Notification index filter tabs correctly isolate unread from all notifications', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-FILTER-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));
    $this->tenant->notify(new BillDueReminderNotification($bill));

    // Mark first notification as read
    $first = $this->tenant->unreadNotifications()->first();
    $first->markAsRead();

    // With filter=all, sees both
    $resAll = $this->actingAs($this->tenant)->get(route('notifications.index', ['filter' => 'all']));
    $resAll->assertOk();
    $resAll->assertSee('Tagihan Baru Diterbitkan');
    $resAll->assertSee('Pengingat Jatuh Tempo Tagihan');

    // With filter=unread, sees only unread
    $resUnread = $this->actingAs($this->tenant)->get(route('notifications.index', ['filter' => 'unread']));
    $resUnread->assertOk();
    $resUnread->assertSee('Pengingat Jatuh Tempo Tagihan');
});

test('16. markAsRead and markAllAsRead return valid JSON when requested via AJAX', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-JSON-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));
    $notif = $this->tenant->unreadNotifications()->first();

    // AJAX single mark as read
    $resSingle = $this->actingAs($this->tenant)
        ->postJson(route('notifications.read', $notif->id));
    $resSingle->assertOk();
    $resSingle->assertJson([
        'success' => true,
        'unread_count' => 0,
    ]);

    // Add another unread notification
    $this->tenant->notify(new BillDueReminderNotification($bill));

    // AJAX mark all as read
    $resAll = $this->actingAs($this->tenant)
        ->postJson(route('notifications.readAll'));
    $resAll->assertOk();
    $resAll->assertJson([
        'success' => true,
        'unread_count' => 0,
    ]);
});

test('17. Owner creating manual bill via owner.bills.store notifies tenant', function () {
    Notification::fake();

    $response = $this->actingAs($this->owner)->post(route('owner.bills.store'), [
        'contract_id' => $this->contract->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'items' => [
            ['description' => 'Sewa Bulanan Manual', 'amount' => 2000000, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect();

    Notification::assertSentTo($this->tenant, InvoiceCreatedNotification::class);
});

test('18. Notification dropdown and topbar render smoothly without regressions on Owner and Tenant portals', function () {
    $bill = Bill::create([
        'bill_number' => 'INV-RENDER-'.Str::random(6),
        'contract_id' => $this->contract->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'type' => BillType::Sewa->value,
        'billing_period' => now()->format('Y-m'),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 2000000,
        'paid_amount' => 0,
        'status' => BillStatus::MenungguPembayaran->value,
    ]);

    $this->tenant->notify(new InvoiceCreatedNotification($bill));

    // Tenant dashboard renders with notification badge
    $resTenant = $this->actingAs($this->tenant)->get(route('tenant.dashboard'));
    $resTenant->assertOk();
    $resTenant->assertSee('Pusat Notifikasi');

    // Owner dashboard renders with notification dropdown
    $resOwner = $this->actingAs($this->owner)->get(route('owner.dashboard'));
    $resOwner->assertOk();
    $resOwner->assertSee('Pusat Notifikasi');
});
