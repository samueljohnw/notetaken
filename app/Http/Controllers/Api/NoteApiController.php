<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteApiController extends Controller
{
    public function index()
    {
        $notes = Note::with('categories')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'title' => $note->title,
                    'content' => $note->content,
                    'categories' => $note->categories->pluck('name')->toArray(),
                    'has_notification' => $note->has_notification,
                    'notification_datetime' => $note->notification_datetime,
                    'notification_recurrence' => $note->notification_recurrence,
                    'created_at' => $note->created_at,
                ];
            });

        return response()->json($notes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'has_notification' => 'boolean',
            'notification_datetime' => 'nullable|date',
            'notification_recurrence' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        // For API requests from ChatGPT, we assume the datetime is already in the user's local time
        // and convert it to UTC by parsing it as UTC
        $notificationDatetime = null;
        if (!empty($validated['notification_datetime'])) {
            $notificationDatetime = \Carbon\Carbon::parse($validated['notification_datetime']);
        }

        $note = Note::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'markdown_file_path' => '', // Will be set by the model
            'attachments' => [],
            'has_notification' => $request->boolean('has_notification'),
            'notification_datetime' => $notificationDatetime,
            'notification_recurrence' => $validated['notification_recurrence'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully!',
            'note' => [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'categories' => [],
                'has_notification' => $note->has_notification,
                'notification_datetime' => $note->notification_datetime,
                'created_at' => $note->created_at,
            ],
        ], 201);
    }
}
