<?php

namespace App\Notifications;

use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Ntfy\Message;
use Wijourdil\NtfyNotificationChannel\Channels\NtfyChannel;

class NoteReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Note $note
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [NtfyChannel::class];
    }

    /**
     * Get the ntfy representation of the notification.
     */
    public function toNtfy(object $notifiable): Message
    {
        $message = new Message();
        $message->topic(config('ntfy-notification-channel.topic'));
        $message->title($this->note->title);

        $body = $this->note->content;

        if ($this->note->notification_recurrence) {
            $body .= " (" . ucfirst($this->note->notification_recurrence) . " reminder)";
        }

        $message->body($body);
        //$message->tags(['memo', 'bell']);
        $message->priority(4);
        $message->clickAction(route('notes.index') . '#note-' . $this->note->id);

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'note_id' => $this->note->id,
            'title' => $this->note->title,
            'notification_datetime' => $this->note->notification_datetime,
            'recurrence' => $this->note->notification_recurrence,
        ];
    }
}
