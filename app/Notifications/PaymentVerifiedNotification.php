<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentVerifiedNotification extends Notification
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
        $billStatusLabel = $bill?->isPaid() ? 'Lunas' : 'Dibayar Sebagian';

        $mail = (new MailMessage)
            ->subject("Pembayaran Berhasil Diverifikasi: {$this->payment->payment_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pembayaran Anda sebesar {$formattedAmount} untuk tagihan {$bill?->bill_number} telah disetujui oleh pemilik kos.")
            ->line("Nomor Transaksi: {$this->payment->payment_number}")
            ->line("Kamar / Properti: {$room?->room_number} - {$property?->name}")
            ->line("Status Tagihan Sekarang: **{$billStatusLabel}**");

        if ($bill && ! $bill->isPaid()) {
            $outstanding = 'Rp '.number_format($bill->outstandingAmount(), 0, ',', '.');
            $mail->line("Sisa Tagihan yang Harus Dibayar: **{$outstanding}**");
        }

        return $mail
            ->action('Lihat Rincian Tagihan', route('tenant.bills.show', $bill))
            ->line('Terima kasih telah melakukan pembayaran tepat waktu.')
            ->salutation('Salam hangat, Tim HUNI');
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
            'title' => 'Pembayaran Berhasil Diverifikasi',
            'message' => "Pembayaran Anda sebesar {$formattedAmount} untuk tagihan {$bill?->bill_number} telah disetujui.",
            'url' => $bill ? route('tenant.bills.show', $bill) : route('tenant.dashboard'),
            'type' => 'payment_verified',
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'bill_id' => $bill?->id,
            'bill_number' => $bill?->bill_number,
            'amount' => (float) $this->payment->amount,
            'bill_status' => $bill?->status,
            'room_number' => $room?->room_number ?? '-',
            'property_name' => $property?->name ?? 'Kos',
            'icon' => 'check-circle',
        ];
    }
}
