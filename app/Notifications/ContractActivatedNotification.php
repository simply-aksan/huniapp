<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contract $contract
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
        $property = $this->contract->property?->name ?? 'Kos';
        $room = $this->contract->room?->room_number ?? '-';
        $startDate = $this->contract->start_date ? $this->contract->start_date->format('d/m/Y') : '-';
        $endDate = $this->contract->end_date ? $this->contract->end_date->format('d/m/Y') : '-';

        return (new MailMessage)
            ->subject("Kontrak Sewa Anda Telah Aktif: {$this->contract->contract_number}")
            ->greeting("Selamat datang, {$notifiable->name}!")
            ->line("Kontrak sewa hunian Anda untuk kamar {$room} di {$property} telah resmi aktif.")
            ->line("Nomor Kontrak: {$this->contract->contract_number}")
            ->line("Periode Sewa: {$startDate} s/d {$endDate} ({$this->contract->duration_months} bulan)")
            ->line('Harga Sewa Bulanan: Rp '.number_format((float) $this->contract->monthly_rent, 0, ',', '.'))
            ->action('Buka Portal HUNI', route('tenant.dashboard'))
            ->line('Anda dapat melihat rincian kamar, inventaris, dan tagihan berkala langsung di dashboard penyewa.')
            ->salutation('Selamat menempati hunian baru Anda, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $property = $this->contract->property?->name ?? 'Kos';
        $room = $this->contract->room?->room_number ?? '-';

        return [
            'title' => 'Kontrak Sewa Aktif',
            'message' => "Kontrak sewa Anda ({$this->contract->contract_number}) untuk kamar {$room} di {$property} telah aktif.",
            'url' => route('tenant.dashboard'),
            'type' => 'contract_activated',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'property_name' => $property,
            'room_number' => $room,
            'duration_months' => $this->contract->duration_months,
            'start_date' => $this->contract->start_date?->toDateString(),
            'end_date' => $this->contract->end_date?->toDateString(),
            'icon' => 'file-text',
        ];
    }
}
