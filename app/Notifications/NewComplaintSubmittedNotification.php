<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewComplaintSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Complaint $complaint
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
        $tenant = $this->complaint->user;
        $property = $this->complaint->property?->name ?? 'Kos';
        $room = $this->complaint->room?->room_number ?? '-';
        $priorityLabel = $this->complaint->priorityLabel();
        $categoryLabel = $this->complaint->categoryLabel();

        return (new MailMessage)
            ->subject("Pengaduan Baru Masuk: #{$this->complaint->ticket_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Penyewa {$tenant?->name} dari kamar {$room} ({$property}) telah mengajukan tiket pengaduan baru.")
            ->line("Nomor Tiket: #{$this->complaint->ticket_number}")
            ->line("Judul: {$this->complaint->title}")
            ->line("Kategori: {$categoryLabel} | Prioritas: **{$priorityLabel}**")
            ->line('Ringkasan Keluhan: "'.mb_strimwidth($this->complaint->description, 0, 150, '...').'"')
            ->action('Tinjau & Tangani Pengaduan', route('owner.complaints.show', $this->complaint))
            ->line('Silakan periksa detail pengaduan dan tetapkan tindak lanjut bagi penyewa.')
            ->salutation('Salam, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tenant = $this->complaint->user;
        $property = $this->complaint->property?->name ?? 'Kos';
        $room = $this->complaint->room?->room_number ?? '-';

        return [
            'title' => 'Pengaduan Baru Masuk',
            'message' => "Pengaduan baru #{$this->complaint->ticket_number} ('{$this->complaint->title}') diajukan oleh penyewa kamar {$room} ({$property}).",
            'url' => route('owner.complaints.show', $this->complaint),
            'type' => 'complaint_created',
            'complaint_id' => $this->complaint->id,
            'ticket_number' => $this->complaint->ticket_number,
            'tenant_name' => $tenant?->name,
            'room_number' => $room,
            'property_name' => $property,
            'priority' => $this->complaint->priority,
            'icon' => 'message-square',
        ];
    }
}
