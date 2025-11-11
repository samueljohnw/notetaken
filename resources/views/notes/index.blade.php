<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notes App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-900 border border-green-600 text-green-200 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Create/Edit Note Form -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 mb-8 border border-gray-700">
            <h1 class="text-3xl font-bold text-gray-100 mb-6">Notes</h1>

            <form id="noteForm" method="POST" action="{{ route('notes.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <input type="hidden" name="note_id" id="noteId">
                <input type="hidden" name="timezone_offset" id="timezoneOffset">

                <!-- Title Field -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Title</label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        required
                        class="w-full px-4 py-2 bg-gray-900 border border-gray-600 text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="Enter note title"
                    >
                    @error('title')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Content Textarea -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-300 mb-2">Content (Markdown)</label>
                    <textarea
                        name="content"
                        id="content"
                        rows="12"
                        required
                        class="w-full px-4 py-2 bg-gray-900 border border-gray-600 text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition font-mono text-sm"
                        placeholder="Write your note in markdown..."
                    ></textarea>
                    @error('content')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Upload -->
                <div>
                    <label for="attachments" class="block text-sm font-medium text-gray-300 mb-2">Attachments (Images/Files)</label>
                    <input
                        type="file"
                        name="attachments[]"
                        id="attachments"
                        multiple
                        class="w-full px-4 py-2 bg-gray-900 border border-gray-600 text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                    >
                    <p class="text-gray-400 text-sm mt-1">Max 10MB per file</p>
                    @error('attachments.*')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Existing Attachments (shown when editing) -->
                <div id="existingAttachmentsWrapper" class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-3 hidden">
                    <h3 class="text-sm font-medium text-gray-300">Existing Attachments</h3>
                    <ul id="existingAttachments" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"></ul>
                    <p id="existingAttachmentsHint" class="text-xs text-gray-400 hidden"></p>
                </div>

                <!-- Notification Settings -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 space-y-3">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="has_notification"
                            id="hasNotification"
                            value="1"
                            class="w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500"
                        >
                        <label for="hasNotification" class="ml-2 text-sm font-medium text-gray-300">Set notification reminder</label>
                    </div>

                    <div id="notificationFields" class="space-y-3 hidden">
                        <div>
                            <label for="notificationDatetime" class="block text-sm font-medium text-gray-300 mb-2">Notification Date & Time</label>
                            <input
                                type="datetime-local"
                                name="notification_datetime"
                                id="notificationDatetime"
                                class="w-full px-4 py-2 bg-gray-800 border border-gray-600 text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            >
                        </div>

                        <div>
                            <label for="notificationRecurrence" class="block text-sm font-medium text-gray-300 mb-2">Recurrence</label>
                            <select
                                name="notification_recurrence"
                                id="notificationRecurrence"
                                class="w-full px-4 py-2 bg-gray-800 border border-gray-600 text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            >
                                <option value="none">None (One-time)</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
                    >
                        <span id="submitButtonText">Create Note</span>
                    </button>
                    <button
                        type="button"
                        id="cancelEdit"
                        class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition font-medium hidden"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Notes List -->
        <div>
            <h2 class="text-2xl font-bold text-gray-100 mb-6">All Notes</h2>

            @if($notes->isEmpty())
                <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700">
                    <p class="text-gray-400 text-center py-8">No notes yet. Create your first note above!</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
                    @foreach($notes as $note)
                        <div class="bg-gray-800 border border-gray-700 rounded-lg shadow hover:shadow-lg transition-shadow group h-full flex flex-col">
                            <a href="#note-{{ $note->id }}" class="block p-4 note-link flex-1" data-note-id="{{ $note->id }}">
                                <h3 class="text-lg font-semibold text-gray-100 mb-2 group-hover:text-blue-400 transition line-clamp-2">
                                    {{ $note->title }}
                                </h3>

                                <p class="text-sm text-gray-300 mb-3 line-clamp-3 whitespace-pre-wrap">{{ $note->content }}</p>

                                @if($note->has_notification)
                                    <div class="mb-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-900 text-blue-200 border border-blue-700">
                                            🔔 <span class="local-time ml-1" data-utc="{{ $note->notification_datetime->toIso8601String() }}">{{ $note->notification_datetime->format('M d, Y g:i A') }}</span>
                                            @if($note->notification_recurrence)
                                                <span class="ml-1">({{ ucfirst($note->notification_recurrence) }})</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($note->attachments && count($note->attachments) > 0)
                                    <div class="text-xs text-gray-400 mb-2">
                                        📎 {{ count($note->attachments) }} attachment(s)
                                    </div>
                                @endif
                            </a>

                            <div class="mt-auto border-t border-gray-700 p-2 grid grid-cols-2 gap-2">
                                <button
                                    onclick="event.preventDefault(); editNote({{ $note->id }})"
                                    class="w-full bg-yellow-600 text-white px-3 py-1.5 rounded hover:bg-yellow-700 transition text-xs font-medium"
                                >
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Are you sure you want to delete this note?');" class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="w-full bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 transition text-xs font-medium"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        // Set timezone offset on page load
        document.getElementById('timezoneOffset').value = new Date().getTimezoneOffset();

        // Convert all UTC times to local time for display
        function convertUTCTimesToLocal() {
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
        }

        // Run on page load
        convertUTCTimesToLocal();

        // Notification checkbox toggle
        const hasNotificationCheckbox = document.getElementById('hasNotification');
        const notificationFields = document.getElementById('notificationFields');

        hasNotificationCheckbox.addEventListener('change', function() {
            if (this.checked) {
                notificationFields.classList.remove('hidden');

                // Set default datetime to current time if empty
                const datetimeInput = document.getElementById('notificationDatetime');
                if (!datetimeInput.value) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    datetimeInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }
            } else {
                notificationFields.classList.add('hidden');
            }
        });

        // Edit Note Function
        async function editNote(noteId) {
            const response = await fetch(`/notes/${noteId}/edit`);
            const note = await response.json();

            // Populate form
            document.getElementById('title').value = note.title;
            document.getElementById('content').value = note.content;
            document.getElementById('noteId').value = note.id;

            // Populate existing attachments list
            const attachmentsWrapper = document.getElementById('existingAttachmentsWrapper');
            const attachmentsList = document.getElementById('existingAttachments');
            const attachmentsHint = document.getElementById('existingAttachmentsHint');
            attachmentsList.innerHTML = '';
            try {
                const storageBase = "{{ asset('storage') }}";
                if (Array.isArray(note.attachments) && note.attachments.length > 0) {
                    let hasImage = false;
                    note.attachments.forEach(att => {
                        const name = att.original_name || (att.path ? att.path.split('/').pop() : 'Attachment');
                        const url = att.path ? `${storageBase}/${att.path}` : '#';
                        const size = att.size ? Math.round(att.size / 1024) + ' KB' : '';
                        const mime = (att.mime_type || '').toLowerCase();

                        const li = document.createElement('li');
                        if (mime.startsWith('image/')) {
                            hasImage = true;
                            li.className = 'group';
                            li.innerHTML = `
                                <button type="button" data-image="${url}" data-alt="${name}" class="block w-full text-left">
                                    <div class="aspect-video bg-gray-800 border border-gray-700 rounded overflow-hidden flex items-center justify-center">
                                        <img src="${url}" alt="${name}" class="h-full w-full object-cover">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-300 truncate">${name}</div>
                                </button>
                                <label class="mt-1 inline-flex items-center gap-2 text-xs text-gray-300">
                                    <input type="checkbox" name="remove_attachments[]" value="${att.path}" class="w-4 h-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                                    <span>Remove</span>
                                </label>`;
                            // Add click handler for modal open
                            li.querySelector('button').addEventListener('click', (e) => {
                                const imgUrl = e.currentTarget.getAttribute('data-image');
                                const alt = e.currentTarget.getAttribute('data-alt');
                                openImageModal(imgUrl, alt);
                            });
                        } else {
                            li.innerHTML = `
                                <a href="${url}" target="_blank" download class="flex items-center justify-between bg-gray-800 border border-gray-700 rounded px-3 py-2 hover:bg-gray-750">
                                    <span class="text-gray-200 text-sm truncate mr-2">${name}</span>
                                    <span class="text-xs text-gray-400">${size}</span>
                                </a>
                                <label class="mt-1 inline-flex items-center gap-2 text-xs text-gray-300">
                                    <input type="checkbox" name="remove_attachments[]" value="${att.path}" class="w-4 h-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                                    <span>Remove</span>
                                </label>`;
                        }
                        attachmentsList.appendChild(li);
                    });
                    if (hasImage) {
                        attachmentsHint.classList.remove('hidden');
                    } else {
                        attachmentsHint.classList.add('hidden');
                    }
                    attachmentsWrapper.classList.remove('hidden');
                } else {
                    attachmentsWrapper.classList.add('hidden');
                }
            } catch (e) {
                attachmentsWrapper.classList.add('hidden');
            }

            // Set notification fields
            if (note.has_notification) {
                document.getElementById('hasNotification').checked = true;
                notificationFields.classList.remove('hidden');

                if (note.notification_datetime) {
                    // Convert UTC to local datetime-local format
                    const date = new Date(note.notification_datetime);
                    // Format as YYYY-MM-DDTHH:MM for datetime-local input
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const localDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                    document.getElementById('notificationDatetime').value = localDateTime;
                }

                if (note.notification_recurrence) {
                    document.getElementById('notificationRecurrence').value = note.notification_recurrence;
                }
            }

            // Update form action and method
            const form = document.getElementById('noteForm');
            form.action = `/notes/${note.id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('submitButtonText').textContent = 'Update Note';
            document.getElementById('cancelEdit').classList.remove('hidden');

            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth' });
        }

        // Cancel Edit
        document.getElementById('cancelEdit').addEventListener('click', function() {
            resetForm();
        });

        function resetForm() {
            const form = document.getElementById('noteForm');
            form.reset();
            form.action = '{{ route("notes.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('noteId').value = '';
            document.getElementById('submitButtonText').textContent = 'Create Note';
            document.getElementById('cancelEdit').classList.add('hidden');
            notificationFields.classList.add('hidden');

            // Hide and clear existing attachments
            const attachmentsWrapper = document.getElementById('existingAttachmentsWrapper');
            const attachmentsList = document.getElementById('existingAttachments');
            attachmentsList.innerHTML = '';
            attachmentsWrapper.classList.add('hidden');
        }

        // Simple image preview modal
        function openImageModal(src, altText) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const caption = document.getElementById('modalCaption');
            modalImg.src = src;
            modalImg.alt = altText || '';
            caption.textContent = altText || '';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            modalImg.src = '';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeImageModal();
        });

        // Copy topic to clipboard
        function copyTopic() {
            const topic = '{{ config("ntfy-notification-channel.topic") }}';
            navigator.clipboard.writeText(topic).then(function() {
                alert('Topic copied to clipboard!');
            }, function(err) {
                alert('Failed to copy topic: ' + err);
            });
        }

        // Handle hash changes (for browser back/forward)
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash;
            if (hash.startsWith('#note-')) {
                const noteId = hash.replace('#note-', '');
                editNote(noteId);
            }
        });

        // Check for hash on page load
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash.startsWith('#note-')) {
                const noteId = hash.replace('#note-', '');
                editNote(noteId);
            }
        });
    </script>
    
    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80" onclick="closeImageModal()"></div>
        <div class="relative h-full w-full flex items-center justify-center p-4">
            <div class="relative max-w-5xl w-full">
                <img id="modalImage" src="" alt="" class="max-h-[80vh] w-auto mx-auto rounded shadow-lg border border-gray-700">
                <div class="mt-2 text-center text-gray-200 text-sm" id="modalCaption"></div>
                <button type="button" onclick="closeImageModal()" class="absolute -top-3 -right-3 bg-gray-800 border border-gray-700 text-gray-200 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-700">✕</button>
            </div>
        </div>
    </div>
</body>
</html>
