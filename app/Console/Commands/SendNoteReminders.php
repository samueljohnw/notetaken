<?php

namespace App\Console\Commands;

use App\Models\Note;
use App\Notifications\NoteReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendNoteReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notes:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for notes with upcoming reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $sixtyMinutesAgo = now()->subMinutes(60);

        // Get notes that have notifications scheduled and are due
        // Expanded to 60 minutes to catch any missed reminders
        $notes = Note::where('has_notification', true)
            ->whereNotNull('notification_datetime')
            ->where('notification_datetime', '<=', $now)
            ->where('notification_datetime', '>=', $sixtyMinutesAgo)
            ->get();

        $sentCount = 0;

        foreach ($notes as $note) {
            // Check if we already sent a notification recently
            if ($note->last_notification_sent_at) {
                $lastSent = $note->last_notification_sent_at;

                // If recurring, check if enough time has passed
                if ($note->notification_recurrence) {
                    $shouldSend = match ($note->notification_recurrence) {
                        'daily' => $lastSent->addDay()->isPast(),
                        'weekly' => $lastSent->addWeek()->isPast(),
                        'monthly' => $lastSent->addMonth()->isPast(),
                        'yearly' => $lastSent->addYear()->isPast(),
                        default => false,
                    };

                    if (!$shouldSend) {
                        continue;
                    }
                } else {
                    // One-time notification already sent
                    continue;
                }
            }

            // Send notification
            Notification::route('ntfy', config('ntfy-notification-channel.topic'))
                ->notify(new NoteReminderNotification($note));

            // Update last sent timestamp
            $note->update(['last_notification_sent_at' => now()]);

            // If it's a one-time notification, schedule next occurrence or disable
            if ($note->notification_recurrence) {
                $nextNotification = match ($note->notification_recurrence) {
                    'daily' => $note->notification_datetime->addDay(),
                    'weekly' => $note->notification_datetime->addWeek(),
                    'monthly' => $note->notification_datetime->addMonth(),
                    'yearly' => $note->notification_datetime->addYear(),
                    default => null,
                };

                if ($nextNotification) {
                    $note->update(['notification_datetime' => $nextNotification]);
                }
            } else {
                // Disable one-time notifications after sending
                $note->update(['has_notification' => false]);
            }

            $sentCount++;
            $this->info("Sent reminder for note: {$note->title}");
        }

        $this->info("Sent {$sentCount} reminder(s).");

        return Command::SUCCESS;
    }
}
