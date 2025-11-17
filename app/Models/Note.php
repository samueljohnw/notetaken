<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class Note extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_checklist',
        'checklist_items',
        'markdown_file_path',
        'attachments',
        'has_notification',
        'notification_datetime',
        'notification_recurrence',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_checklist' => 'boolean',
        'checklist_items' => 'array',
        'has_notification' => 'boolean',
        'notification_datetime' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($note) {
            $note->saveMarkdownFile();
        });

        static::updated(function ($note) {
            $note->saveMarkdownFile();
        });

        static::deleted(function ($note) {
            $note->deleteMarkdownFile();
        });
    }

    public function saveMarkdownFile(): void
    {
        $filename = $this->id . '-' . \Illuminate\Support\Str::slug($this->title) . '.md';
        $path = 'notes/' . $filename;

        $content = "# {$this->title}\n\n";
        $content .= "Created: {$this->created_at}\n";
        $content .= "Updated: {$this->updated_at}\n\n";

        if ($this->has_notification) {
            $content .= "**Notification**: {$this->notification_datetime}";
            if ($this->notification_recurrence) {
                $content .= " (Recurring: {$this->notification_recurrence})";
            }
            $content .= "\n\n";
        }

        $content .= "---\n\n";
        $content .= $this->content;

        Storage::put($path, $content);

        if ($this->markdown_file_path !== $path) {
            $oldPath = $this->markdown_file_path;
            $this->markdown_file_path = $path;
            $this->saveQuietly();

            if ($oldPath && Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }
        }
    }

    public function deleteMarkdownFile(): void
    {
        if ($this->markdown_file_path && Storage::exists($this->markdown_file_path)) {
            Storage::delete($this->markdown_file_path);
        }
    }

    public function getRenderedContentAttribute(): string
    {
        // Configure environment with GFM extension for task lists
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $converter = new MarkdownConverter($environment);

        return $converter->convert($this->content)->getContent();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'note_category');
    }
}
