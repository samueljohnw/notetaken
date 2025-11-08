<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $note->title }} - Notes App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-900 border border-green-600 text-green-200 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('notes.index') }}" class="inline-flex items-center text-blue-400 hover:text-blue-300 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Notes
            </a>
        </div>

        <!-- Note Content -->
        <div class="bg-gray-800 rounded-lg shadow-md p-8 border border-gray-700">
            <!-- Note Header -->
            <div class="border-b border-gray-700 pb-6 mb-6">
                <h1 class="text-4xl font-bold text-gray-100 mb-4">{{ $note->title }}</h1>

                <div class="flex flex-wrap gap-4 text-sm text-gray-400">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Created <span class="local-time" data-utc="{{ $note->created_at->toIso8601String() }}">{{ $note->created_at->format('M d, Y g:i A') }}</span>
                    </div>

                    @if($note->created_at != $note->updated_at)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Updated <span class="local-time" data-utc="{{ $note->updated_at->toIso8601String() }}">{{ $note->updated_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif

                    @if($note->has_notification && $note->notification_datetime)
                        <div class="flex items-center text-blue-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Reminder: <span class="local-time" data-utc="{{ $note->notification_datetime->toIso8601String() }}">{{ $note->notification_datetime->format('M d, Y g:i A') }}</span>
                            @if($note->notification_recurrence)
                                <span class="ml-1">({{ ucfirst($note->notification_recurrence) }})</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Rendered Markdown Content -->
            <div class="prose prose-invert prose-lg max-w-none mb-6">
                {!! $note->rendered_content !!}
            </div>

            <!-- Attachments -->
            @if($note->attachments && count($note->attachments) > 0)
                <div class="border-t border-gray-700 pt-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-100 mb-4">Attachments ({{ count($note->attachments) }})</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($note->attachments as $attachment)
                            <div class="border border-gray-700 bg-gray-900 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-100 break-all">{{ $attachment['original_name'] }}</p>
                                        <p class="text-sm text-gray-400 mt-1">
                                            {{ number_format($attachment['size'] / 1024, 2) }} KB
                                        </p>
                                    </div>
                                    <a
                                        href="{{ asset('storage/' . $attachment['path']) }}"
                                        target="_blank"
                                        class="ml-2 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition"
                                    >
                                        View
                                    </a>
                                </div>

                                @if(str_starts_with($attachment['mime_type'], 'image/'))
                                    <div class="mt-3">
                                        <img
                                            src="{{ asset('storage/' . $attachment['path']) }}"
                                            alt="{{ $attachment['original_name'] }}"
                                            class="max-w-full h-auto rounded"
                                        >
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="border-t border-gray-700 pt-6 mt-6">
                <div class="flex gap-3">
                    <a
                        href="{{ route('notes.index') }}"
                        class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition"
                    >
                        Back to Notes
                    </a>

                    <a
                        href="{{ route('notes.index') }}#edit-{{ $note->id }}"
                        onclick="event.preventDefault(); window.location.href='{{ route('notes.index') }}'; setTimeout(() => editNote({{ $note->id }}), 100);"
                        class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 transition"
                    >
                        Edit
                    </a>

                    <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Are you sure you want to delete this note?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition"
                        >
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Convert all UTC times to local time for display
        document.querySelectorAll('.local-time').forEach(function(element) {
            const utcString = element.getAttribute('data-utc');
            if (utcString) {
                const date = new Date(utcString);
                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                };
                element.textContent = date.toLocaleString('en-US', options);
            }
        });
    </script>
</body>
</html>
