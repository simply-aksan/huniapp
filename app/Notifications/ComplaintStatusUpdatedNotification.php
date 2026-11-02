<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Complaint $complaint,
        public string $previousStatus,
        public string $newStatus
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
        $statusLabel = $this->complaint->statusLabel();
        $property = $this->complaint->property?->name ?? 'Kos';
        $room = $this->complaint->room?->room_number ?? '-';

        $message = (new MailMessage)
            ->subject("Update Status Pengaduan: #{$this->complaint->ticket_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Status tiket pengaduan #{$this->complaint->ticket_number} untuk kamar {$room} di {$property} telah diperbarui menjadi: **{$statusLabel}**.")
            ->line("Judul Tiket: {$this->complaint->title}");

        if ($this->complaint->handled_by) {
            $message->line("Penanggung Jawab / Teknisi: {$this->complaint->handled_by}");
        }

        if ($this->complaint->estimated_completion) {
            $message->line("Estimasi Selesai: {$this->complaint->estimated_completion->format('d/m/Y')}");
        }

        return $message
            ->action('Lihat Detail Tiket', route('tenant.complaints.show', $this->complaint))
            ->line('Anda dapat melihat tanggapan terbaru atau membalas pesan melalui portal HUNI.')
            ->salutation('Salam, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Status Pengaduan Diperbarui',
            'message' => "Status tiket #{$this->complaint->ticket_number} ('{$this->complaint->title}') telah diperbarui menjadi {$this->complaint->statusLabel()}.",
            'url' => route('tenant.complaints.show', $this->complaint),
            'type' => 'complaint_status_updated',
            'complaint_id' => $this->complaint->id,
            'ticket_number' => $this->complaint->ticket_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->complaint->statusLabel(),
            'icon' => 'alert-circle',
        ];
    }
}
