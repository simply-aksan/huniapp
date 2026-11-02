<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Bill $bill
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
        $property = $this->bill->contract?->property ?? $this->bill->room?->property;
        $room = $this->bill->room ?? $this->bill->contract?->room;
        $formattedAmount = 'Rp '.number_format((float) $this->bill->total_amount, 0, ',', '.');
        $dueDate = $this->bill->due_date?->format('d/m/Y') ?? '-';

        return (new MailMessage)
            ->subject("Tagihan Baru Diterbitkan: {$this->bill->bill_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Tagihan sewa untuk kamar {$room?->room_number} di {$property?->name} periode {$this->bill->formattedPeriod()} telah diterbitkan.")
            ->line("Nomor Tagihan: {$this->bill->bill_number}")
            ->line("Total Tagihan: {$formattedAmount}")
            ->line("Batas Jatuh Tempo: {$dueDate}")
            ->action('Lihat & Bayar Tagihan', route('tenant.bills.show', $this->bill))
            ->line('Silakan lakukan pembayaran dan unggah bukti transfer sebelum tanggal jatuh tempo.')
            ->salutation('Terima kasih, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $property = $this->bill->contract?->property ?? $this->bill->room?->property;
        $room = $this->bill->room ?? $this->bill->contract?->room;

        return [
            'title' => 'Tagihan Baru Diterbitkan',
            'message' => "Tagihan {$this->bill->bill_number} sebesar Rp ".number_format((float) $this->bill->total_amount, 0, ',', '.')." untuk kamar {$room?->room_number} telah diterbitkan.",
            'url' => route('tenant.bills.show', $this->bill),
            'type' => 'bill_created',
            'bill_id' => $this->bill->id,
            'bill_number' => $this->bill->bill_number,
            'total_amount' => (float) $this->bill->total_amount,
            'property_name' => $property?->name ?? 'Kos',
            'room_number' => $room?->room_number ?? '-',
            'period' => $this->bill->billing_period,
            'due_date' => $this->bill->due_date?->toDateString(),
            'icon' => 'file-text',
        ];
    }
}
