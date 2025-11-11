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
            // Use database transaction with lock to prevent race conditions
            \DB::transaction(function () use ($note, &$sentCount) {
                // Re-fetch with lock to ensure we have the latest data
                $lockedNote = Note::where('id', $note->id)->lockForUpdate()->first();

                if (!$lockedNote) {
                    return;
                }

                // Check if we already sent a notification recently
                if ($lockedNote->last_notification_sent_at) {
                    $lastSent = $lockedNote->last_notification_sent_at;

                    // Prevent duplicate sends within 5 minutes (safety net for race conditions)
                    if ($lastSent->diffInMinutes(now()) < 5) {
                        return;
                    }

                    // If recurring, check if enough time has passed
                    if ($lockedNote->notification_recurrence) {
                        $shouldSend = match ($lockedNote->notification_recurrence) {
                            'daily' => $lastSent->addDay()->isPast(),
                            'weekly' => $lastSent->addWeek()->isPast(),
                            'monthly' => $lastSent->addMonth()->isPast(),
                            'yearly' => $lastSent->addYear()->isPast(),
                            default => false,
                        };

                        if (!$shouldSend) {
                            return;
                        }
                    } else {
                        // One-time notification already sent
                        return;
                    }
                }

                // Update last sent timestamp BEFORE sending to prevent duplicates
                $lockedNote->update(['last_notification_sent_at' => now()]);

                // Send notification
                Notification::route('ntfy', config('ntfy-notification-channel.topic'))
                    ->notify(new NoteReminderNotification($lockedNote));

                // If it's a one-time notification, schedule next occurrence or disable
                if ($lockedNote->notification_recurrence) {
                    $nextNotification = match ($lockedNote->notification_recurrence) {
                        'daily' => $lockedNote->notification_datetime->addDay(),
                        'weekly' => $lockedNote->notification_datetime->addWeek(),
                        'monthly' => $lockedNote->notification_datetime->addMonth(),
                        'yearly' => $lockedNote->notification_datetime->addYear(),
                        default => null,
                    };

                    if ($nextNotification) {
                        $lockedNote->update(['notification_datetime' => $nextNotification]);
                    }
                } else {
                    // Disable one-time notifications after sending
                    $lockedNote->update(['has_notification' => false]);
                }

                $sentCount++;
                $this->info("Sent reminder for note: {$lockedNote->title}");
            });
        }

        $this->info("Sent {$sentCount} reminder(s).");

        return Command::SUCCESS;
    }
}
