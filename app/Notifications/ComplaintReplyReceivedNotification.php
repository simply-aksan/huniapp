<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintReplyReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Complaint $complaint,
        public ComplaintReply $reply,
        public User $author
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
        $roleLabel = $this->author->isOwner() ? 'Pemilik Kos' : 'Penyewa';
        $authorName = "{$this->author->name} ({$roleLabel})";
        $snippet = mb_strimwidth($this->reply->message, 0, 160, '...');

        $targetUrl = $notifiable->isOwner()
            ? route('owner.complaints.show', $this->complaint)
            : route('tenant.complaints.show', $this->complaint);

        return (new MailMessage)
            ->subject("Balasan Baru pada Pengaduan #{$this->complaint->ticket_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("{$authorName} telah mengirim balasan pada tiket pengaduan #{$this->complaint->ticket_number} ('{$this->complaint->title}').")
            ->line("Pesan: \"{$snippet}\"")
            ->action('Buka & Balas Pengaduan', $targetUrl)
            ->line('Anda dapat melihat seluruh riwayat percakapan secara langsung di aplikasi HUNI.')
            ->salutation('Salam, Tim HUNI');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roleLabel = $this->author->isOwner() ? 'Pemilik' : 'Penyewa';
        $targetUrl = $notifiable->isOwner()
            ? route('owner.complaints.show', $this->complaint)
            : route('tenant.complaints.show', $this->complaint);

        return [
            'title' => 'Balasan Baru Pengaduan',
            'message' => "{$this->author->name} ({$roleLabel}) membalas tiket #{$this->complaint->ticket_number}: \"".mb_strimwidth($this->reply->message, 0, 80, '...').'"',
            'url' => $targetUrl,
            'type' => 'complaint_reply_received',
            'complaint_id' => $this->complaint->id,
            'ticket_number' => $this->complaint->ticket_number,
            'author_name' => $this->author->name,
            'author_id' => $this->author->id,
            'icon' => 'message-square',
        ];
    }
}
