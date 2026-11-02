<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPaymentSubmittedNotification extends Notification
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
        $tenant = $this->payment->user;
        $property = $bill?->contract?->property ?? $bill?->room?->property;
        $room = $bill?->room ?? $bill?->contract?->room;
        $formattedAmount = 'Rp '.number_format((float) $this->payment->amount, 0, ',', '.');
        $paymentDate = $this->payment->payment_date ? $this->payment->payment_date->format('d/m/Y') : now()->format('d/m/Y');

        return (new MailMessage)
            ->subject("Pembayaran Baru Menunggu Verifikasi: {$this->payment->payment_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Penyewa {$tenant?->name} telah mengunggah bukti pembayaran untuk tagihan {$bill?->bill_number} (Kamar {$room?->room_number} - {$property?->name}).")
            ->line("Nomor Transaksi: {$this->payment->payment_number}")
            ->line("Nominal Pembayaran: {$formattedAmount}")
            ->line("Tanggal Pembayaran: {$paymentDate}")
            ->line('Metode Pembayaran: '.ucwords(str_replace('_', ' ', (string) $this->payment->payment_method)))
            ->action('Verifikasi Pembayaran', route('owner.payments.show', $this->payment))
            ->line('Silakan periksa bukti transfer dan konfirmasi pembayaran melalui dashboard HUNI.')
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
        $tenant = $this->payment->user;
        $property = $bill?->contract?->property ?? $bill?->room?->property;
        $room = $bill?->room ?? $bill?->contract?->room;
        $formattedAmount = 'Rp '.number_format((float) $this->payment->amount, 0, ',', '.');

        return [
            'title' => 'Pembayaran Baru Menunggu Verifikasi',
            'message' => "Penyewa {$tenant?->name} telah mengirim pembayaran sebesar {$formattedAmount} untuk kamar {$room?->room_number} ({$property?->name}).",
            'url' => route('owner.payments.show', $this->payment),
            'type' => 'payment_submitted',
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'bill_id' => $bill?->id,
            'bill_number' => $bill?->bill_number,
            'amount' => (float) $this->payment->amount,
            'tenant_name' => $tenant?->name,
            'room_number' => $room?->room_number ?? '-',
            'property_name' => $property?->name ?? 'Kos',
            'icon' => 'credit-card',
        ];
    }
}
