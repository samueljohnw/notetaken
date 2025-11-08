<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    public function index()
    {
        $notes = Note::orderBy('created_at', 'desc')->get();
        return view('notes.index', compact('notes'));
    }

    public function show(Note $note)
    {
        return view('notes.show', compact('note'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'has_notification' => 'boolean',
            'notification_datetime' => 'nullable|date',
            'notification_recurrence' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
            'timezone_offset' => 'nullable|integer', // Timezone offset in minutes
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Convert notification_datetime from user's local time to UTC
        $notificationDatetime = null;
        if ($validated['notification_datetime']) {
            // JavaScript's getTimezoneOffset() returns positive minutes for timezones behind UTC
            // e.g., Mountain Time (UTC-7) returns 420
            // To convert local to UTC, we need to ADD the offset
            // Local 6:04 PM + 420 minutes (7 hours) = UTC 1:04 AM next day
            $timezoneOffset = (int) ($validated['timezone_offset'] ?? 0);
            $notificationDatetime = \Carbon\Carbon::parse($validated['notification_datetime'])
                ->addMinutes($timezoneOffset);
        }

        Note::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'markdown_file_path' => '', // Will be set by the model
            'attachments' => $attachmentPaths,
            'has_notification' => $request->boolean('has_notification'),
            'notification_datetime' => $notificationDatetime,
            'notification_recurrence' => $validated['notification_recurrence'] === 'none' ? null : ($validated['notification_recurrence'] ?? null),
        ]);

        return redirect()->route('notes.index')->with('success', 'Note created successfully!');
    }

    public function edit(Note $note)
    {
        return response()->json($note);
    }

    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'has_notification' => 'boolean',
            'notification_datetime' => 'nullable|date',
            'notification_recurrence' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'attachments.*' => 'nullable|file|max:10240',
            'timezone_offset' => 'nullable|integer', // Timezone offset in minutes
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        $attachmentPaths = $note->attachments ?? [];

        // Remove selected attachments (delete files too)
        $toRemove = collect($request->input('remove_attachments', []))->filter();
        if ($toRemove->isNotEmpty() && !empty($attachmentPaths)) {
            foreach ($attachmentPaths as $idx => $attachment) {
                if (isset($attachment['path']) && $toRemove->contains($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                    unset($attachmentPaths[$idx]);
                }
            }
            $attachmentPaths = array_values($attachmentPaths);
        }
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Convert notification_datetime from user's local time to UTC
        $notificationDatetime = null;
        if ($validated['notification_datetime']) {
            // JavaScript's getTimezoneOffset() returns positive minutes for timezones behind UTC
            // e.g., Mountain Time (UTC-7) returns 420
            // To convert local to UTC, we need to ADD the offset
            // Local 6:04 PM + 420 minutes (7 hours) = UTC 1:04 AM next day
            $timezoneOffset = (int) ($validated['timezone_offset'] ?? 0);
            $notificationDatetime = \Carbon\Carbon::parse($validated['notification_datetime'])
                ->addMinutes($timezoneOffset);
        }

        $note->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'attachments' => $attachmentPaths,
            'has_notification' => $request->boolean('has_notification'),
            'notification_datetime' => $notificationDatetime,
            'notification_recurrence' => $validated['notification_recurrence'] === 'none' ? null : ($validated['notification_recurrence'] ?? null),
        ]);

        return redirect()->route('notes.index')->with('success', 'Note updated successfully!');
    }

    public function destroy(Note $note)
    {
        // Delete attachments
        if ($note->attachments) {
            foreach ($note->attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }
        }

        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Note deleted successfully!');
    }
}
