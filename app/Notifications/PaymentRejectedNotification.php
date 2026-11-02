<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $bill = $this->payment->bill;
        $property = $bill?->contract?->property ?? $bill?->room?->property;
        $room = $bill?->room ?? $bill?->contract?->room;
        $formattedAmount = 'Rp '.number_format((float) $this->payment->amount, 0, ',', '.');
        $reason = $this->payment->rejection_reason ?? 'Bukti transfer tidak sesuai atau tidak terbaca.';

        return (new MailMessage)
            ->subject("Pembayaran Ditolak: {$this->payment->payment_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pengajuan pembayaran Anda sebesar {$formattedAmount} untuk tagihan {$bill?->bill_number} (Kamar {$room?->room_number} - {$property?->name}) tidak dapat disetujui.")
            ->line("Alasan Penolakan: **{$reason}**")
            ->line('Silakan periksa kembali rincian tagihan Anda dan unggah bukti transfer yang valid.')
            ->action('Periksa Tagihan & Kirim Ulang', route('tenant.bills.show', $bill))
            ->line('Jika Anda merasa ada kekeliruan, silakan hubungi pemilik kos atau buat tiket pengaduan.')
            ->salutation('Salam hormat, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bill = $this->payment->bill;
        $property = $bill?->contract?->property ?? $bill?->room?->property;
        $room = $bill?->room ?? $bill?->contract?->room;
        $formattedAmount = 'Rp '.number_format((float) $this->payment->amount, 0, ',', '.');

        return [
            'title' => 'Pembayaran Ditolak',
            'message' => "Pembayaran Anda sebesar {$formattedAmount} untuk tagihan {$bill?->bill_number} ditolak. Alasan: {$this->payment->rejection_reason}",
            'url' => $bill ? route('tenant.bills.show', $bill) : route('tenant.dashboard'),
            'type' => 'payment_rejected',
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'bill_id' => $bill?->id,
            'bill_number' => $bill?->bill_number,
            'amount' => (float) $this->payment->amount,
            'rejection_reason' => $this->payment->rejection_reason,
            'room_number' => $room?->room_number ?? '-',
            'property_name' => $property?->name ?? 'Kos',
            'icon' => 'x-circle',
        ];
    }
}
