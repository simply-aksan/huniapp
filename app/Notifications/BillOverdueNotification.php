<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillOverdueNotification extends Notification
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
            ->subject("Pemberitahuan: Tagihan {$this->bill->bill_number} Telah Melewati Jatuh Tempo")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Tagihan sewa Anda untuk kamar {$room?->room_number} di {$property?->name} telah **melewati tanggal jatuh tempo ({$dueDate})**.")
            ->line("Nomor Tagihan: {$this->bill->bill_number}")
            ->line("Total Tunggakan: **{$outstanding}**")
            ->action('Selesaikan Pembayaran Sekarang', route('tenant.bills.show', $this->bill))
            ->line('Mohon segera menyelesaikan pembayaran Anda untuk kenyamanan tinggal dan menghindari pembatasan fasilitas kos.')
            ->salutation('Salam, Tim HUNI');
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
            'title' => 'Tagihan Melewati Jatuh Tempo',
            'message' => "Tagihan {$this->bill->bill_number} sebesar {$outstanding} telah melewati batas jatuh tempo ({$dueDate}). Mohon segera lakukan pembayaran.",
            'url' => route('tenant.bills.show', $this->bill),
            'type' => 'bill_overdue',
            'bill_id' => $this->bill->id,
            'bill_number' => $this->bill->bill_number,
            'outstanding_amount' => $this->bill->outstandingAmount(),
            'due_date' => $this->bill->due_date?->toDateString(),
            'room_number' => $room?->room_number ?? '-',
            'property_name' => $property?->name ?? 'Kos',
            'icon' => 'alert-triangle',
        ];
    }
}
