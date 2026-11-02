<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillDueReminderNotification extends Notification
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
        $outstanding = 'Rp '.number_format($this->bill->outstandingAmount(), 0, ',', '.');
        $dueDate = $this->bill->due_date ? $this->bill->due_date->format('d/m/Y') : '-';

        return (new MailMessage)
            ->subject("Pengingat: Tagihan {$this->bill->bill_number} Jatuh Tempo Segera")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Ini adalah pengingat bahwa tagihan sewa Anda untuk kamar {$room?->room_number} di {$property?->name} akan jatuh tempo pada tanggal **{$dueDate}**.")
            ->line("Nomor Tagihan: {$this->bill->bill_number}")
            ->line("Sisa yang Harus Dibayar: **{$outstanding}**")
            ->line("Periode Tagihan: {$this->bill->formattedPeriod()}")
            ->action('Bayar Sekarang', route('tenant.bills.show', $this->bill))
            ->line('Mohon lakukan pembayaran dan unggah bukti transfer sebelum tanggal jatuh tempo agar terhindar dari denda atau kendala sewa.')
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
        $outstanding = 'Rp '.number_format($this->bill->outstandingAmount(), 0, ',', '.');
        $dueDate = $this->bill->due_date ? $this->bill->due_date->format('d/m/Y') : '-';

        return [
            'title' => 'Pengingat Jatuh Tempo Tagihan',
            'message' => "Tagihan {$this->bill->bill_number} sebesar {$outstanding} akan jatuh tempo pada {$dueDate}.",
            'url' => route('tenant.bills.show', $this->bill),
            'type' => 'bill_due_reminder',
            'bill_id' => $this->bill->id,
            'bill_number' => $this->bill->bill_number,
            'outstanding_amount' => $this->bill->outstandingAmount(),
            'due_date' => $this->bill->due_date?->toDateString(),
            'room_number' => $room?->room_number ?? '-',
            'property_name' => $property?->name ?? 'Kos',
            'icon' => 'clock',
        ];
    }
}
