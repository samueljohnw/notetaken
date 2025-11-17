<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notes App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde@latest/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/easymde@latest/dist/easymde.min.js"></script>
    <style>
        /* EasyMDE Dark Theme Customization */
        .EasyMDEContainer {
            background-color: #111827 !important;
        }
        .EasyMDEContainer .CodeMirror {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
            border: 1px solid #374151 !important;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            min-height: 200px;
        }
        .EasyMDEContainer .CodeMirror-cursor {
            border-left-color: #f3f4f6 !important;
        }
        .EasyMDEContainer .CodeMirror-selected {
            background: #374151 !important;
        }
        .EasyMDEContainer .cm-header {
            color: #60a5fa !important;
        }
        .EasyMDEContainer .cm-strong {
            color: #fbbf24 !important;
        }
        .EasyMDEContainer .cm-em {
            color: #34d399 !important;
        }
        .EasyMDEContainer .cm-link {
            color: #60a5fa !important;
        }
        .EasyMDEContainer .cm-url {
            color: #818cf8 !important;
        }
        .EasyMDEContainer .cm-quote {
            color: #9ca3af !important;
        }
        .editor-toolbar {
            background-color: #1f2937 !important;
            border: 1px solid #374151 !important;
            border-radius: 0.375rem 0.375rem 0 0;
            border-bottom: none !important;
        }
        .editor-toolbar button {
            color: #9ca3af !important;
        }
        .editor-toolbar button:hover {
            background-color: #374151 !important;
            color: #f3f4f6 !important;
            border-color: #4b5563 !important;
        }
        .editor-toolbar button.active {
            background-color: #374151 !important;
            color: #60a5fa !important;
        }
        .editor-toolbar i.separator {
            border-left-color: #374151 !important;
            border-right-color: #374151 !important;
        }
        .editor-preview {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
        }
        .editor-preview-side {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
            border: 1px solid #374151 !important;
        }
        .CodeMirror .CodeMirror-code .cm-tag {
            color: #34d399 !important;
        }
        .CodeMirror .CodeMirror-code .cm-attribute {
            color: #fbbf24 !important;
        }
        .CodeMirror .CodeMirror-code .cm-string {
            color: #60a5fa !important;
        }
        .CodeMirror-focused .CodeMirror-selected {
            background: #374151 !important;
        }

        /* Checklist items */
        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem;
            background-color: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        .checklist-item:hover {
            background-color: #374151;
        }
        .checklist-item input[type="checkbox"] {
            margin-top: 0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        .checklist-item-text {
            flex: 1;
            background-color: transparent;
            border: none;
            color: #f3f4f6;
            outline: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .checklist-item-text.checked {
            text-decoration: line-through;
            opacity: 0.6;
        }
        .checklist-item-remove {
            color: #ef4444;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .checklist-item:hover .checklist-item-remove {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-900 border border-green-600 text-green-200 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Create/Edit Note Form -->
        <div class="bg-gray-800 rounded-lg shadow-md p-4 mb-8 border border-gray-700 max-w-2xl mx-auto">
            <h1 class="text-xl font-bold text-gray-100 mb-3"><a href="/">Quick Note</a></h1>

            <form id="noteForm" method="POST" action="{{ route('notes.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <input type="hidden" name="note_id" id="noteId">
                <input type="hidden" name="timezone_offset" id="timezoneOffset">
                <input type="hidden" name="is_checklist" id="isChecklist" value="0">
                <input type="hidden" name="checklist_items" id="checklistItemsInput">
                <input type="file" name="attachments[]" id="attachments" multiple class="hidden">

                <!-- Title Field -->
                <div>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        required
                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 text-gray-100 text-lg font-semibold rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="Title"
                    >
                    @error('title')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Content Textarea (Note Mode) -->
                <div id="noteContentWrapper" style="min-height: 200px;">
                    <textarea
                        name="content"
                        id="content"
                        rows="6"
                        class="w-full px-3 py-2 bg-gray-900 border border-gray-600 text-gray-100 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm resize-none"
                        placeholder="Write your note..."
                    ></textarea>
                    @error('content')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Checklist Items (Checklist Mode) -->
                <div id="checklistWrapper" class="hidden" style="min-height: 200px;">
                    <div class="bg-gray-900 border border-gray-600 rounded p-3 space-y-2 min-h-[160px]" id="checklistContainer">
                        <!-- Checklist items will be added here dynamically -->
                    </div>
                    <button
                        type="button"
                        id="addChecklistItem"
                        class="mt-2 text-sm text-blue-400 hover:text-blue-300 transition flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add item
                    </button>
                </div>

                <!-- Existing Attachments (shown when editing) -->
                <div id="existingAttachmentsWrapper" class="bg-gray-900 border border-gray-700 rounded p-3 space-y-2 hidden">
                    <h3 class="text-xs font-medium text-gray-300">Existing Attachments</h3>
                    <ul id="existingAttachments" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></ul>
                    <p id="existingAttachmentsHint" class="text-xs text-gray-400 hidden"></p>
                </div>

                <!-- Hidden Fields for Notifications -->
                <input type="hidden" name="has_notification" id="hasNotification" value="0">
                <input type="hidden" name="notification_datetime" id="notificationDatetime">
                <input type="hidden" name="notification_recurrence" id="notificationRecurrence" value="none">

                <!-- Bottom Action Bar -->
                <div class="flex items-center justify-between gap-2 pt-1">
                    <div class="flex items-center gap-1 relative">
                        <!-- Checklist Toggle -->
                        <button
                            type="button"
                            id="checklistToggleBtn"
                            class="text-gray-400 hover:text-gray-200 transition p-1.5 rounded hover:bg-gray-700"
                            title="Toggle checklist"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </button>

                        <!-- Categories Button -->
                        <button
                            type="button"
                            id="categoriesBtn"
                            class="text-gray-400 hover:text-gray-200 transition p-1.5 rounded hover:bg-gray-700 relative"
                            title="Add categories"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </button>

                        <!-- Categories Popover -->
                        <div id="categoriesPopover" class="hidden absolute bottom-full left-0 mb-2 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-3 w-64 z-50">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-medium text-gray-200">Categories</h3>
                                <button type="button" onclick="document.getElementById('categoriesPopover').classList.add('hidden')" class="text-gray-400 hover:text-gray-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                                @foreach($categories as $category)
                                    <label class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs cursor-pointer hover:bg-gray-700 transition">
                                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="w-3 h-3 rounded category-checkbox">
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $category->color }}"></span>
                                        <span class="text-gray-300">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                                @if($categories->isEmpty())
                                    <p class="text-xs text-gray-400">No categories yet. Create one in the sidebar!</p>
                                @endif
                            </div>
                        </div>

                        <!-- Reminder Button -->
                        <button
                            type="button"
                            id="reminderBtn"
                            class="text-gray-400 hover:text-gray-200 transition p-1.5 rounded hover:bg-gray-700 relative"
                            title="Set reminder"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </button>

                        <!-- Reminder Popover -->
                        <div id="reminderPopover" class="hidden absolute bottom-full left-0 mb-2 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-3 w-64 z-50">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-medium text-gray-200">Set Reminder</h3>
                                <button type="button" onclick="document.getElementById('reminderPopover').classList.add('hidden')" class="text-gray-400 hover:text-gray-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Date & Time</label>
                                    <input
                                        type="datetime-local"
                                        id="reminderDatetimeInput"
                                        class="w-full px-2 py-1.5 bg-gray-900 border border-gray-600 text-gray-100 text-xs rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Repeat</label>
                                    <select
                                        id="reminderRecurrenceInput"
                                        class="w-full px-2 py-1.5 bg-gray-900 border border-gray-600 text-gray-100 text-xs rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    >
                                        <option value="none">Once</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    id="clearReminderBtn"
                                    class="text-xs text-red-400 hover:text-red-300 transition"
                                >
                                    Clear reminder
                                </button>
                            </div>
                        </div>

                        <!-- Attachments Button -->
                        <button
                            type="button"
                            onclick="document.getElementById('attachments').click()"
                            class="text-gray-400 hover:text-gray-200 transition p-1.5 rounded hover:bg-gray-700"
                            title="Attach files"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                        </button>

                        <span id="attachmentCount" class="text-xs text-gray-400 hidden"></span>
                    </div>

                    <div class="flex gap-2 items-center">
                        <span id="autosaveStatus" class="text-xs text-gray-400 hidden"></span>
                        <button
                            type="button"
                            id="cancelEdit"
                            class="bg-gray-700 text-gray-300 px-4 py-1.5 text-sm rounded hover:bg-gray-600 transition hidden"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded hover:bg-blue-700 transition font-medium"
                        >
                            <span id="submitButtonText">Save Note</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Collapsible Categories -->
        <div class="mb-6">
            <button
                id="categoriesToggle"
                class="w-full flex items-center justify-between px-4 py-2 bg-gray-800/50 border border-gray-700/50 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 transition"
            >
                <span>Categories</span>
                <svg id="categoriesChevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="categoriesContent" class="hidden mt-2 p-4 bg-gray-800/30 border border-gray-700/30 rounded-lg">
                <!-- Category List -->
                <ul id="categoryList" class="space-y-0.5 mb-3">
                    <li>
                        <button class="w-full text-left px-2 py-1.5 rounded text-xs text-gray-300 hover:bg-gray-800/50 transition category-filter active" data-category-id="all">
                            All Notes
                        </button>
                    </li>
                    @foreach($categories as $category)
                        <li class="group">
                            <div class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs text-gray-300 hover:bg-gray-800/50 transition">
                                <button class="flex items-center gap-1.5 flex-1 text-left category-filter" data-category-id="{{ $category->id }}">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $category->color }}"></span>
                                    <span class="truncate">{{ $category->name }}</span>
                                </button>
                                <button
                                    onclick="deleteCategory({{ $category->id }})"
                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-300 p-0.5 rounded hover:bg-red-900/30 transition flex-shrink-0 ml-1"
                                    type="button"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <!-- Add Category Form -->
                <div class="border-t border-gray-700/50 pt-3 mt-1">
                    <form id="addCategoryForm">
                        @csrf
                        <div class="flex gap-1.5 mb-2">
                            <input
                                type="text"
                                id="newCategoryName"
                                placeholder="New category..."
                                class="flex-1 px-2 py-1 bg-gray-800/50 border border-gray-600/50 text-gray-100 text-xs rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            >
                            <input
                                type="color"
                                id="newCategoryColor"
                                value="#3b82f6"
                                class="w-7 h-7 bg-gray-800/50 border border-gray-600/50 rounded cursor-pointer"
                                title="Pick a color"
                            >
                        </div>
                        <button
                            type="submit"
                            class="w-full bg-blue-600 text-white px-2 py-1 text-xs rounded hover:bg-blue-700 transition font-medium"
                        >
                            + Add
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notes Grid -->
        <div>
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

                                @if($note->is_checklist && $note->checklist_items)
                                    <div class="space-y-1 mb-3">
                                        @foreach(array_slice($note->checklist_items, 0, 3) as $item)
                                            <div class="flex items-center gap-2 text-sm text-gray-300">
                                                <input type="checkbox" {{ $item['checked'] ? 'checked' : '' }} disabled class="w-3.5 h-3.5 pointer-events-none">
                                                <span class="{{ $item['checked'] ? 'line-through opacity-60' : '' }}">{{ $item['text'] }}</span>
                                            </div>
                                        @endforeach
                                        @if(count($note->checklist_items) > 3)
                                            <div class="text-xs text-gray-400 pl-5">
                                                +{{ count($note->checklist_items) - 3 }} more items
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-300 mb-3 line-clamp-3 whitespace-pre-wrap">{{ $note->content }}</p>
                                @endif

                                @if($note->attachments && count($note->attachments) > 0)
                                    <div class="text-xs text-gray-400 mb-2">
                                        📎 {{ count($note->attachments) }} attachment(s)
                                    </div>
                                @endif
                            </a>

                            <div class="mt-auto border-t border-gray-700 p-2 flex items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($note->has_notification)
                                        <div class="notification-badge" data-notification-time="{{ $note->notification_datetime->toIso8601String() }}" data-note-id="{{ $note->id }}">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-900 text-blue-200 border border-blue-700">
                                                🔔 <span class="local-time ml-1" data-utc="{{ $note->notification_datetime->toIso8601String() }}">{{ $note->notification_datetime->format('M d, Y g:i A') }}</span>
                                                @if($note->notification_recurrence)
                                                    <span class="ml-1">({{ ucfirst($note->notification_recurrence) }})</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endif

                                    @if($note->categories->isNotEmpty())
                                        @foreach($note->categories as $category)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-gray-200" style="background-color: {{ $category->color }}33; border: 1px solid {{ $category->color }}" data-category-id="{{ $category->id }}">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $category->color }}"></span>
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="p-1.5 text-red-400 hover:text-red-300 hover:bg-red-900/20 rounded transition flex-shrink-0"
                                        title="Delete note"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
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

        // Initialize EasyMDE editor
        let easyMDE;
        document.addEventListener('DOMContentLoaded', function() {
            easyMDE = new EasyMDE({
                element: document.getElementById('content'),
                spellChecker: false,
                status: false,
                placeholder: 'Write your note... ',
                minHeight: '200px',
                toolbar: [
                    'bold', 'italic', 'heading', '|',
                    'unordered-list', 'ordered-list', 'check-list', '|',
                    'link', 'image', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ],
                shortcuts: {
                    toggleBold: 'Cmd-B',
                    toggleItalic: 'Cmd-I',
                    togglePreview: 'Cmd-P'
                },
                renderingConfig: {
                    codeSyntaxHighlighting: true,
                },
                previewRender: function(plainText) {
                    // Basic markdown preview rendering
                    return this.parent.markdown(plainText);
                }
            });

            // Setup autosave after EasyMDE is initialized
            setupAutosave();
        });

        // Autosave functionality
        let autosaveTimeout;
        const autosaveStatus = document.getElementById('autosaveStatus');

        function debounce(func, delay) {
            return function() {
                clearTimeout(autosaveTimeout);
                autosaveTimeout = setTimeout(func, delay);
            };
        }

        function showAutosaveStatus(message, isError = false) {
            autosaveStatus.textContent = message;
            autosaveStatus.classList.remove('hidden');
            if (isError) {
                autosaveStatus.classList.add('text-red-400');
                autosaveStatus.classList.remove('text-gray-400', 'text-green-400');
            } else if (message.includes('Saved')) {
                autosaveStatus.classList.add('text-white-400');
                autosaveStatus.classList.remove('text-gray-400', 'text-red-400');
            } else {
                autosaveStatus.classList.add('text-gray-400');
                autosaveStatus.classList.remove('text-green-400', 'text-red-400');
            }

            // Hide "Saved" message after 2 seconds
            if (message.includes('Saved')) {
                setTimeout(() => {
                    autosaveStatus.classList.add('hidden');
                }, 2000);
            }
        }

        async function performAutosave() {
            const noteId = document.getElementById('noteId').value;

            // Only autosave if we're editing an existing note
            if (!noteId) return;

            const title = document.getElementById('title').value;
            if (!title || title.trim() === '') return; // Don't save if title is empty

            showAutosaveStatus('Saving...');

            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('title', title);

                // Send boolean values properly
                const isChecklist = document.getElementById('isChecklist').value === '1';
                formData.append('is_checklist', isChecklist ? '1' : '0');
                formData.append('timezone_offset', document.getElementById('timezoneOffset').value);

                // Handle content based on mode
                if (isChecklist) {
                    // Checklist mode
                    const items = [];
                    document.querySelectorAll('.checklist-item').forEach(item => {
                        const checkbox = item.querySelector('.checklist-checkbox');
                        const text = item.querySelector('.checklist-item-text');
                        if (text && text.value.trim()) {
                            items.push({
                                text: text.value.trim(),
                                checked: checkbox.checked
                            });
                        }
                    });
                    if (items.length > 0) {
                        formData.append('checklist_items', JSON.stringify(items));
                    } else {
                        // Send empty array for checklists with no items
                        formData.append('checklist_items', JSON.stringify([]));
                    }
                    formData.append('content', '');
                } else {
                    // Note mode
                    const content = easyMDE.value() || '';
                    formData.append('content', content);
                }

                // Add notification fields
                const hasNotification = document.getElementById('hasNotification').value === '1';
                formData.append('has_notification', hasNotification ? '1' : '0');

                const notificationDatetime = document.getElementById('notificationDatetime').value;
                if (hasNotification && notificationDatetime) {
                    formData.append('notification_datetime', notificationDatetime);
                } else if (!hasNotification) {
                    // Clear notification if disabled
                    formData.append('notification_datetime', '');
                }

                const notificationRecurrence = document.getElementById('notificationRecurrence').value || 'none';
                formData.append('notification_recurrence', notificationRecurrence);

                // Add categories - only send if there are selected categories
                const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
                selectedCategories.forEach(catId => {
                    formData.append('categories[]', catId);
                });

                const response = await fetch(`/notes/${noteId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (response.ok) {
                    showAutosaveStatus('Saved');
                    // Update the page to reflect the saved state
                    const data = await response.text(); // Laravel redirects, so we just mark as saved
                } else {
                    // Log error details for debugging
                    const errorText = await response.text();
                    console.error('Autosave failed:', response.status, errorText);
                    console.error('FormData contents:');
                    for (let pair of formData.entries()) {
                        console.error(pair[0] + ': ' + pair[1]);
                    }
                    showAutosaveStatus('Error saving', true);
                }
            } catch (error) {
                console.error('Autosave error:', error);
                showAutosaveStatus('Error saving', true);
            }
        }

        const debouncedAutosave = debounce(performAutosave, 1500);

        function setupAutosave() {
            // Autosave on title change
            document.getElementById('title').addEventListener('input', debouncedAutosave);

            // Autosave on EasyMDE content change
            if (easyMDE) {
                easyMDE.codemirror.on('change', debouncedAutosave);
            }

            // Autosave on category changes
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', debouncedAutosave);
            });
        }

        // Checklist toggle button
        const checklistToggleBtn = document.getElementById('checklistToggleBtn');
        const noteContentWrapper = document.getElementById('noteContentWrapper');
        const checklistWrapper = document.getElementById('checklistWrapper');
        const isChecklistInput = document.getElementById('isChecklist');
        const checklistContainer = document.getElementById('checklistContainer');

        checklistToggleBtn.addEventListener('click', function() {
            const isCurrentlyChecklist = isChecklistInput.value === '1';

            if (isCurrentlyChecklist) {
                // Switch to note mode
                noteContentWrapper.classList.remove('hidden');
                checklistWrapper.classList.add('hidden');
                isChecklistInput.value = '0';
                document.getElementById('content').removeAttribute('disabled');
                checklistToggleBtn.classList.remove('text-blue-400');
                checklistToggleBtn.classList.add('text-gray-400');
            } else {
                // Switch to checklist mode
                checklistWrapper.classList.remove('hidden');
                noteContentWrapper.classList.add('hidden');
                isChecklistInput.value = '1';
                document.getElementById('content').setAttribute('disabled', 'disabled');
                checklistToggleBtn.classList.remove('text-gray-400');
                checklistToggleBtn.classList.add('text-blue-400');

                // Add initial item if empty
                if (checklistContainer.children.length === 0) {
                    addChecklistItem();
                }
            }
        });

        // Categories popover
        document.getElementById('categoriesBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const popover = document.getElementById('categoriesPopover');
            popover.classList.toggle('hidden');
            document.getElementById('reminderPopover').classList.add('hidden');
        });

        // Reminder popover
        document.getElementById('reminderBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const popover = document.getElementById('reminderPopover');
            popover.classList.toggle('hidden');
            document.getElementById('categoriesPopover').classList.add('hidden');
        });

        // Close popovers when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#categoriesBtn') && !e.target.closest('#categoriesPopover')) {
                document.getElementById('categoriesPopover').classList.add('hidden');
            }
            if (!e.target.closest('#reminderBtn') && !e.target.closest('#reminderPopover')) {
                document.getElementById('reminderPopover').classList.add('hidden');
            }
        });

        // Reminder functionality
        const reminderDatetimeInput = document.getElementById('reminderDatetimeInput');
        const reminderRecurrenceInput = document.getElementById('reminderRecurrenceInput');
        const hasNotificationInput = document.getElementById('hasNotification');
        const notificationDatetime = document.getElementById('notificationDatetime');
        const notificationRecurrence = document.getElementById('notificationRecurrence');

        reminderDatetimeInput.addEventListener('change', function() {
            if (this.value) {
                hasNotificationInput.value = '1';
                notificationDatetime.value = this.value;
                document.getElementById('reminderBtn').classList.remove('text-gray-400');
                document.getElementById('reminderBtn').classList.add('text-blue-400');
                debouncedAutosave();
            }
        });

        reminderRecurrenceInput.addEventListener('change', function() {
            notificationRecurrence.value = this.value;
            debouncedAutosave();
        });

        document.getElementById('clearReminderBtn').addEventListener('click', function() {
            reminderDatetimeInput.value = '';
            reminderRecurrenceInput.value = 'none';
            hasNotificationInput.value = '0';
            notificationDatetime.value = '';
            notificationRecurrence.value = 'none';
            document.getElementById('reminderBtn').classList.remove('text-blue-400');
            document.getElementById('reminderBtn').classList.add('text-gray-400');
            document.getElementById('reminderPopover').classList.add('hidden');
            debouncedAutosave();
        });

        // Checklist functionality
        let checklistItemId = 0;

        function addChecklistItem(text = '', checked = false) {
            checklistItemId++;
            const itemDiv = document.createElement('div');
            itemDiv.className = 'checklist-item';
            itemDiv.dataset.itemId = checklistItemId;

            itemDiv.innerHTML = `
                <input type="checkbox" ${checked ? 'checked' : ''} class="checklist-checkbox">
                <input type="text" class="checklist-item-text ${checked ? 'checked' : ''}" placeholder="List item" value="${text}">
                <button type="button" class="checklist-item-remove hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;

            const checkbox = itemDiv.querySelector('.checklist-checkbox');
            const textInput = itemDiv.querySelector('.checklist-item-text');
            const removeBtn = itemDiv.querySelector('.checklist-item-remove');

            // Toggle strike-through on checkbox change
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    textInput.classList.add('checked');
                } else {
                    textInput.classList.remove('checked');
                }
                // Trigger autosave on checkbox change
                debouncedAutosave();
            });

            // Remove item
            removeBtn.addEventListener('click', function() {
                itemDiv.remove();
                // Trigger autosave after removing item
                debouncedAutosave();
            });

            // Add new item on Enter key
            textInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addChecklistItem();
                }
            });

            // Trigger autosave on text input
            textInput.addEventListener('input', debouncedAutosave);

            checklistContainer.appendChild(itemDiv);
            textInput.focus();
        }

        document.getElementById('addChecklistItem').addEventListener('click', function() {
            addChecklistItem();
        });

        // Serialize checklist items before form submission
        document.getElementById('noteForm').addEventListener('submit', function(e) {
            if (isChecklistInput.value === '1') {
                const items = [];
                document.querySelectorAll('.checklist-item').forEach(item => {
                    const checkbox = item.querySelector('.checklist-checkbox');
                    const text = item.querySelector('.checklist-item-text');
                    if (text.value.trim()) {
                        items.push({
                            text: text.value.trim(),
                            checked: checkbox.checked
                        });
                    }
                });
                document.getElementById('checklistItemsInput').value = JSON.stringify(items);
            }
        });

        // Categories accordion toggle
        const categoriesToggle = document.getElementById('categoriesToggle');
        const categoriesContent = document.getElementById('categoriesContent');
        const categoriesChevron = document.getElementById('categoriesChevron');

        categoriesToggle.addEventListener('click', function() {
            const isHidden = categoriesContent.classList.contains('hidden');
            if (isHidden) {
                categoriesContent.classList.remove('hidden');
                categoriesChevron.style.transform = 'rotate(180deg)';
            } else {
                categoriesContent.classList.add('hidden');
                categoriesChevron.style.transform = 'rotate(0deg)';
            }
        });


        // Add category
        document.getElementById('addCategoryForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const name = document.getElementById('newCategoryName').value.trim();
            const color = document.getElementById('newCategoryColor').value;

            if (!name) return;

            try {
                const response = await fetch('/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name, color })
                });

                if (response.ok) {
                    location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Error creating category');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error creating category');
            }
        });

        // Delete category
        async function deleteCategory(categoryId) {
            if (!confirm('Delete this category? Notes will not be deleted.')) return;

            try {
                const response = await fetch(`/categories/${categoryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    location.reload();
                } else {
                    alert('Error deleting category');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting category');
            }
        }

        // Category filtering
        document.querySelectorAll('.category-filter').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-category-id');

                // Update active state
                document.querySelectorAll('.category-filter').forEach(btn => {
                    btn.classList.remove('active', 'bg-gray-700');
                });
                this.classList.add('active', 'bg-gray-700');

                // Filter notes
                const notes = document.querySelectorAll('[data-note-id]');
                notes.forEach(noteLink => {
                    const noteCard = noteLink.closest('.bg-gray-800');
                    if (categoryId === 'all') {
                        noteCard.style.display = '';
                    } else {
                        // Check if note has this category
                        const hasCategory = noteLink.querySelector(`[data-category-id="${categoryId}"]`);
                        noteCard.style.display = hasCategory ? '' : 'none';
                    }
                });
            });
        });

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


        // Attachment file input change handler
        document.getElementById('attachments').addEventListener('change', function(e) {
            const count = e.target.files.length;
            const counter = document.getElementById('attachmentCount');
            if (count > 0) {
                counter.textContent = `${count} file${count > 1 ? 's' : ''} selected`;
                counter.classList.remove('hidden');
            } else {
                counter.classList.add('hidden');
            }
        });

        // Edit Note Function
        async function editNote(noteId) {
            const response = await fetch(`/notes/${noteId}/edit`);
            const note = await response.json();

            // Populate form
            document.getElementById('title').value = note.title;
            document.getElementById('noteId').value = note.id;

            // Handle checklist vs note mode
            if (note.is_checklist && note.checklist_items) {
                // Switch to checklist mode
                checklistWrapper.classList.remove('hidden');
                noteContentWrapper.classList.add('hidden');
                isChecklistInput.value = '1';
                checklistToggleBtn.classList.remove('text-gray-400');
                checklistToggleBtn.classList.add('text-blue-400');
                checklistContainer.innerHTML = '';
                note.checklist_items.forEach(item => {
                    addChecklistItem(item.text, item.checked);
                });
            } else {
                // Switch to note mode
                noteContentWrapper.classList.remove('hidden');
                checklistWrapper.classList.add('hidden');
                isChecklistInput.value = '0';
                checklistToggleBtn.classList.remove('text-blue-400');
                checklistToggleBtn.classList.add('text-gray-400');
                easyMDE.value(note.content);
            }

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
                hasNotificationInput.value = '1';

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

                    // Update both hidden field and popover input
                    notificationDatetime.value = localDateTime;
                    reminderDatetimeInput.value = localDateTime;

                    // Highlight reminder button
                    document.getElementById('reminderBtn').classList.remove('text-gray-400');
                    document.getElementById('reminderBtn').classList.add('text-blue-400');
                }

                if (note.notification_recurrence) {
                    notificationRecurrence.value = note.notification_recurrence;
                    reminderRecurrenceInput.value = note.notification_recurrence;
                } else {
                    notificationRecurrence.value = 'none';
                    reminderRecurrenceInput.value = 'none';
                }
            } else {
                hasNotificationInput.value = '0';
                notificationDatetime.value = '';
                reminderDatetimeInput.value = '';
                notificationRecurrence.value = 'none';
                reminderRecurrenceInput.value = 'none';
                document.getElementById('reminderBtn').classList.remove('text-blue-400');
                document.getElementById('reminderBtn').classList.add('text-gray-400');
            }

            // Set categories
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            if (note.categories && Array.isArray(note.categories)) {
                note.categories.forEach(category => {
                    const checkbox = document.querySelector(`.category-checkbox[value="${category.id}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            // Update form action and method
            const form = document.getElementById('noteForm');
            form.action = `/notes/${note.id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('submitButtonText').textContent = 'Update';
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
            easyMDE.value('');
            form.action = '{{ route("notes.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('noteId').value = '';
            document.getElementById('submitButtonText').textContent = 'Save Note';
            document.getElementById('cancelEdit').classList.add('hidden');

            // Reset notification fields
            hasNotificationInput.value = '0';
            notificationDatetime.value = '';
            reminderDatetimeInput.value = '';
            notificationRecurrence.value = 'none';
            reminderRecurrenceInput.value = 'none';
            document.getElementById('reminderBtn').classList.remove('text-blue-400');
            document.getElementById('reminderBtn').classList.add('text-gray-400');

            // Reset category fields
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Hide and clear existing attachments
            const attachmentsWrapper = document.getElementById('existingAttachmentsWrapper');
            const attachmentsList = document.getElementById('existingAttachments');
            attachmentsList.innerHTML = '';
            attachmentsWrapper.classList.add('hidden');

            // Reset attachment counter
            document.getElementById('attachmentCount').classList.add('hidden');

            // Reset to note mode (default)
            noteContentWrapper.classList.remove('hidden');
            checklistWrapper.classList.add('hidden');
            isChecklistInput.value = '0';
            checklistContainer.innerHTML = '';
            document.getElementById('content').removeAttribute('disabled');
            checklistToggleBtn.classList.remove('text-blue-400');
            checklistToggleBtn.classList.add('text-gray-400');

            // Close any open popovers
            document.getElementById('categoriesPopover').classList.add('hidden');
            document.getElementById('reminderPopover').classList.add('hidden');
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

        // Auto-hide expired notification badges
        function checkExpiredNotifications() {
            const now = new Date();
            const badges = document.querySelectorAll('.notification-badge');

            badges.forEach(badge => {
                const notificationTime = new Date(badge.getAttribute('data-notification-time'));

                // If the notification time has passed, hide the badge with a fade-out effect
                if (now > notificationTime) {
                    badge.style.transition = 'opacity 0.5s ease-out';
                    badge.style.opacity = '0';

                    // Remove from DOM after fade-out completes
                    setTimeout(() => {
                        badge.remove();
                    }, 500);
                }
            });
        }

        // Check immediately on page load
        checkExpiredNotifications();

        // Check every 10 seconds for expired notifications
        setInterval(checkExpiredNotifications, 10000);
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
