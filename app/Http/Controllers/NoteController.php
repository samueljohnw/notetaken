<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    public function index()
    {
        $notes = Note::with('categories')->orderBy('created_at', 'desc')->get();
        $categories = Category::orderBy('order')->get();
        return view('notes.index', compact('notes', 'categories'));
    }

    public function show(Note $note)
    {
        return view('notes.show', compact('note'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_checklist' => 'boolean',
            'checklist_items' => 'nullable|json',
            'has_notification' => 'boolean',
            'notification_datetime' => 'nullable|date',
            'notification_recurrence' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
            'timezone_offset' => 'nullable|integer', // Timezone offset in minutes
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
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

        // Handle checklist data
        $isChecklist = $request->boolean('is_checklist');
        $checklistItems = null;
        if ($isChecklist && $request->filled('checklist_items')) {
            $checklistItems = json_decode($validated['checklist_items'], true);
        }

        $note = Note::create([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'is_checklist' => $isChecklist,
            'checklist_items' => $checklistItems,
            'markdown_file_path' => '', // Will be set by the model
            'attachments' => $attachmentPaths,
            'has_notification' => $request->boolean('has_notification'),
            'notification_datetime' => $notificationDatetime,
            'notification_recurrence' => $validated['notification_recurrence'] === 'none' ? null : ($validated['notification_recurrence'] ?? null),
        ]);

        // Attach categories if provided
        if ($request->has('categories')) {
            $note->categories()->sync($request->input('categories', []));
        }

        return redirect()->route('notes.index')->with('success', 'Note created successfully!');
    }

    public function edit(Note $note)
    {
        $note->load('categories');
        return response()->json($note);
    }

    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_checklist' => 'boolean',
            'checklist_items' => 'nullable|json',
            'has_notification' => 'boolean',
            'notification_datetime' => 'nullable|date',
            'notification_recurrence' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'attachments.*' => 'nullable|file|max:10240',
            'timezone_offset' => 'nullable|integer', // Timezone offset in minutes
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
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

        // Handle checklist data
        $isChecklist = $request->boolean('is_checklist');
        $checklistItems = null;
        if ($isChecklist && $request->filled('checklist_items')) {
            $checklistItems = json_decode($validated['checklist_items'], true);
        }

        // Prepare update data
        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'is_checklist' => $isChecklist,
            'checklist_items' => $checklistItems,
            'attachments' => $attachmentPaths,
            'has_notification' => $request->boolean('has_notification'),
            'notification_datetime' => $notificationDatetime,
            'notification_recurrence' => $validated['notification_recurrence'] === 'none' ? null : ($validated['notification_recurrence'] ?? null),
        ];

        // Always reset last_notification_sent_at when updating notification settings
        // This ensures edited notifications are treated as new
        if ($request->boolean('has_notification') && $notificationDatetime) {
            $updateData['last_notification_sent_at'] = null;
        }

        $note->update($updateData);

        // Sync categories
        $note->categories()->sync($request->input('categories', []));

        // Return JSON for AJAX requests (autosave), redirect for regular form submissions
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Note saved successfully']);
        }

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

    public function updateChecklistItem(Request $request, Note $note, int $itemIndex)
    {
        $validated = $request->validate([
            'checked' => 'required|boolean',
        ]);

        if (!$note->is_checklist || !$note->checklist_items) {
            return response()->json(['error' => 'Not a checklist note'], 400);
        }

        $items = $note->checklist_items;

        if (!isset($items[$itemIndex])) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $items[$itemIndex]['checked'] = $validated['checked'];
        $note->checklist_items = $items;
        $note->save();

        return response()->json(['success' => true]);
    }
}
