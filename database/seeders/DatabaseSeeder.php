<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Models\Facility;
use App\Models\Inventory;
use App\Models\Invitation;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\PropertyRule;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with minimal test data to verify relations.
     */
    public function run(): void
    {
        // 1. Identity: Owner & Tenant users
        $owner = User::firstOrCreate(
            ['email' => 'aksan@huniapp.test'],
            [
                'name' => 'Aksan Zulkarnain',
                'phone' => '081234567890',
                'nik' => '3271234505030001',
                'occupation' => 'Pemilik Kos',
                'role' => UserRole::Owner,
                'password' => Hash::make('password'),
            ]
        );

        $tenant = User::firstOrCreate(
            ['email' => 'budi@huniapp.test'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567891',
                'nik' => '3271234505030002',
                'occupation' => 'Mahasiswa • UGM',
                'role' => UserRole::Tenant,
                'password' => Hash::make('password'),
            ]
        );

        // 2. Property: Kos Harmoni
        $property = Property::firstOrCreate(
            ['slug' => 'kos-harmoni'],
            [
                'owner_id' => $owner->id,
                'name' => 'Kos Harmoni',
                'address' => 'Jl. Pogung No. 12, Sinduadi',
                'city' => 'Sleman',
                'district' => 'Mlati',
                'postal_code' => '55284',
                'description' => 'Kos modern dan nyaman dekat kampus UGM.',
                'is_primary' => true,
                'status' => 'aktif',
            ]
        );

        if ($property->owner_id !== $owner->id) {
            $property->update(['owner_id' => $owner->id]);
        }

        // 3. Rooms in Kos Harmoni
        $roomA3 = Room::firstOrCreate(
            ['property_id' => $property->id, 'room_number' => 'A3'],
            [
                'floor' => 1,
                'type' => 'Deluxe',
                'price' => 2000000,
                'size' => '4 x 3 m (12 m²)',
                'capacity' => 1,
                'status' => 'terisi',
                'description' => 'Kamar lantai 1 dengan balkon dan kamar mandi dalam.',
                'internal_notes' => 'Kamar favorit, sirkulasi udara sangat baik.',
            ]
        );

        $roomB1 = Room::firstOrCreate(
            ['property_id' => $property->id, 'room_number' => 'B1'],
            [
                'floor' => 2,
                'type' => 'Standard',
                'price' => 1500000,
                'size' => '3 x 3 m (9 m²)',
                'capacity' => 1,
                'status' => 'kosong',
                'description' => 'Kamar lantai 2 siap huni.',
            ]
        );

        // 4. Facilities
        $wifi = Facility::firstOrCreate(
            ['name' => 'WiFi'],
            ['category' => 'umum', 'icon' => 'wifi', 'description' => 'Akses internet cepat 100 Mbps']
        );
        $ac = Facility::firstOrCreate(
            ['name' => 'AC'],
            ['category' => 'kamar', 'icon' => 'ac', 'description' => 'Air Conditioner hemat daya']
        );
        $kamarMandi = Facility::firstOrCreate(
            ['name' => 'Kamar Mandi Dalam'],
            ['category' => 'kamar', 'icon' => 'door', 'description' => 'Shower, kloset duduk, dan cermin']
        );

        $property->facilities()->syncWithoutDetaching([$wifi->id]);
        $roomA3->facilities()->syncWithoutDetaching([$ac->id, $kamarMandi->id]);

        // 5. Inventory
        Inventory::firstOrCreate(
            ['property_id' => $property->id, 'room_id' => $roomA3->id, 'name' => 'AC Daikin 1 PK'],
            [
                'serial_number' => 'DKN00123',
                'category' => 'Elektronik',
                'brand' => 'Daikin',
                'model' => 'FTKQ25SVM4',
                'location' => 'Kamar A3 (Lantai 1)',
                'quantity' => 1,
                'condition' => 'baik',
                'purchase_date' => '2024-01-12',
                'purchase_price' => 4500000,
                'warranty_until' => '2027-01-12',
                'last_checked_at' => now()->toDateString(),
                'notes' => 'Servis rutin berkala setiap 6 bulan.',
            ]
        );

        // 6. Invitation for vacant room
        $invitation = Invitation::firstOrCreate(
            ['token' => 'inv-token-demo-123456'],
            [
                'invited_by' => $owner->id,
                'room_id' => $roomB1->id,
                'name' => 'Andi Pratama',
                'email' => 'andi@huniapp.test',
                'phone' => '081299998888',
                'message' => 'Selamat datang di Kos Harmoni, silakan lengkapi pendaftaran.',
                'send_via' => 'email',
                'status' => 'aktif',
                'valid_until' => now()->addDays(7),
            ]
        );

        if ($invitation->invited_by !== $owner->id) {
            $invitation->update(['invited_by' => $owner->id]);
        }

        // 7. Contract for Budi Santoso
        $contract = Contract::firstOrCreate(
            ['contract_number' => 'KT-2025-001'],
            [
                'property_id' => $property->id,
                'room_id' => $roomA3->id,
                'user_id' => $tenant->id,
                'start_date' => '2025-02-01',
                'end_date' => '2026-01-31',
                'duration_months' => 12,
                'monthly_rent' => 2000000,
                'total_amount' => 24000000,
                'deposit_amount' => 2000000,
                'deposit_status' => 'lunas',
                'payment_method' => 'Transfer Bank',
                'status' => 'aktif',
                'notes' => 'Penghuni kooperatif dan menjaga kebersihan kamar.',
            ]
        );

        // 8. Contract Document
        ContractDocument::firstOrCreate(
            ['contract_id' => $contract->id, 'file_name' => 'Kontrak_KT-2025-001.pdf'],
            [
                'file_path' => 'contracts/Kontrak_KT-2025-001.pdf',
                'file_size' => 250880,
                'mime_type' => 'application/pdf',
            ]
        );

        // 9. Bill
        $bill = Bill::firstOrCreate(
            ['bill_number' => 'INV-2025-032'],
            [
                'contract_id' => $contract->id,
                'user_id' => $tenant->id,
                'room_id' => $roomA3->id,
                'billing_period' => 'Februari 2026',
                'issue_date' => '2026-01-28',
                'due_date' => '2026-02-01',
                'total_amount' => 2000000,
                'paid_amount' => 2000000,
                'status' => 'lunas',
                'notes' => 'Tagihan sewa bulanan kamar A3.',
            ]
        );

        // 10. Bill Item
        BillItem::firstOrCreate(
            ['bill_id' => $bill->id, 'description' => 'Sewa Kamar - Februari 2026'],
            [
                'amount' => 2000000,
                'quantity' => 1,
            ]
        );

        // 11. Payment
        $payment = Payment::firstOrCreate(
            ['payment_number' => 'PAY-2026-001'],
            [
                'bill_id' => $bill->id,
                'user_id' => $tenant->id,
                'amount' => 2000000,
                'payment_method' => 'Transfer Bank',
                'reference_number' => 'TRF12345678',
                'payment_date' => '2026-02-01 10:24:00',
                'status' => 'berhasil',
                'verified_by' => $owner->id,
                'verified_at' => '2026-02-01 10:30:00',
                'notes' => 'Pembayaran diterima via BCA dan diverifikasi owner.',
            ]
        );

        // 12. Payment Proof
        PaymentProof::firstOrCreate(
            ['payment_id' => $payment->id, 'file_name' => 'bukti_transfer_feb2026.jpg'],
            [
                'file_path' => 'payments/bukti_transfer_feb2026.jpg',
                'file_size' => 312000,
                'mime_type' => 'image/jpeg',
            ]
        );

        // 13. Complaint
        $complaint = Complaint::firstOrCreate(
            ['ticket_number' => 'KMP-2026-0004'],
            [
                'property_id' => $property->id,
                'room_id' => $roomA3->id,
                'user_id' => $tenant->id,
                'title' => 'Lampu kamar tidak menyala',
                'description' => 'Lampu utama di kamar A3 tidak menyala sejak tadi malam. Mohon bantuan untuk pengecekan.',
                'category' => 'Listrik',
                'priority' => 'tinggi',
                'status' => 'diproses',
                'handled_by' => 'Andi Pratama (Teknisi)',
                'estimated_completion' => now()->addHours(6),
            ]
        );

        // 14. Complaint Reply
        ComplaintReply::firstOrCreate(
            ['complaint_id' => $complaint->id, 'user_id' => $owner->id],
            [
                'message' => 'Baik Mas Budi, teknisi kami sudah dijadwalkan datang siang ini pukul 14:00.',
            ]
        );

        // 15. Property Rules for Kos Harmoni
        PropertyRule::firstOrCreate(
            ['property_id' => $property->id, 'title' => 'Jam Malam & Kunci Gerbang'],
            [
                'content' => 'Gerbang kos dikunci pukul 23:00 WIB setiap malam. Penghuni yang pulang lewat jam malam wajib melapor ke penjaga kos.',
                'category' => 'jam_malam',
                'order' => 1,
                'is_active' => true,
            ]
        );

        PropertyRule::firstOrCreate(
            ['property_id' => $property->id, 'title' => 'Ketentuan Tamu & Menginap'],
            [
                'content' => 'Tamu lawan jenis dilarang masuk ke dalam kamar. Tamu hanya boleh diterima di ruang tamu bersama hingga pukul 21:00 WIB.',
                'category' => 'tamu',
                'order' => 2,
                'is_active' => true,
            ]
        );

        PropertyRule::firstOrCreate(
            ['property_id' => $property->id, 'title' => 'Kebersihan & Pembuangan Sampah'],
            [
                'content' => 'Sampah kamar wajib dibuang ke tempat sampah utama di depan kos sebelum pukul 08:00 pagi setiap hari.',
                'category' => 'kebersihan',
                'order' => 3,
                'is_active' => true,
            ]
        );

        PropertyRule::firstOrCreate(
            ['property_id' => $property->id, 'title' => 'Fasilitas Dapur Bersama'],
            [
                'content' => 'Dapur bersama dan mesin cuci dapat digunakan secara gratis oleh seluruh penghuni kos. Harap mencuci peralatan masak setelah digunakan.',
                'category' => 'fasilitas',
                'order' => 4,
                'is_active' => true,
            ]
        );

        // 16. Property FAQs for Kos Harmoni
        PropertyFaq::firstOrCreate(
            ['property_id' => $property->id, 'question' => 'Apakah ada jam malam di kos ini?'],
            [
                'answer' => 'Ya, gerbang kos dikunci setiap hari pada pukul 23:00 WIB. Jika ada keperluan mendesak, silakan hubungi penjaga kos.',
                'category' => 'jam_malam',
                'order' => 1,
                'is_active' => true,
            ]
        );

        PropertyFaq::firstOrCreate(
            ['property_id' => $property->id, 'question' => 'Bolehkah teman atau keluarga menginap?'],
            [
                'answer' => 'Tamu sejenis boleh menginap maksimal 2 malam berturut-turut dengan melapor terlebih dahulu kepada pemilik kos atau pengelola.',
                'category' => 'tamu',
                'order' => 2,
                'is_active' => true,
            ]
        );

        PropertyFaq::firstOrCreate(
            ['property_id' => $property->id, 'question' => 'Bagaimana cara pembayaran sewa kos?'],
            [
                'answer' => 'Pembayaran dilakukan setiap bulan paling lambat tanggal 5 melalui transfer bank BCA yang tertera di menu tagihan.',
                'category' => 'pembayaran',
                'order' => 3,
                'is_active' => true,
            ]
        );
    }
}
