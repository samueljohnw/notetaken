# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Application Overview

This is a Laravel 12 notes application with scheduled notification reminders via Ntfy. Notes support markdown content, file attachments, and recurring notifications (daily/weekly/monthly/yearly).

## Development Commands

### Initial Setup
```bash
composer setup
```
This runs: `composer install`, creates `.env` from `.env.example`, generates app key, runs migrations, and builds frontend assets.

### Running the Application
```bash
composer dev
```
This starts three concurrent processes:
- Laravel development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen --tries=1`)
- Vite dev server (`npm run dev`)

### Testing
```bash
composer test
# Or directly:
php artisan test
# Run specific test:
php artisan test --filter=TestName
```

Uses Pest PHP for testing framework.

### Code Formatting
```bash
./vendor/bin/pint
```

### Database
```bash
php artisan migrate          # Run migrations
php artisan migrate:fresh    # Fresh database (drops all tables)
php artisan db:seed          # Run seeders
```

Uses SQLite by default (`database/database.sqlite`).

## Architecture

### Note Model Lifecycle
The `Note` model (app/Models/Note.php) has a critical auto-sync feature:
- **On create/update**: Automatically generates a markdown file in `storage/app/notes/` with format `{id}-{slug}.md`
- **On delete**: Automatically removes the associated markdown file
- **Path tracking**: `markdown_file_path` column stores the file location and updates if title changes

### Notification System
**Command**: `php artisan notes:send-reminders`

Scheduled to run every minute (configure in `routes/console.php` or task scheduler). The command:
1. Queries notes with `has_notification=true` and `notification_datetime <= now()`
2. Uses database locks (`lockForUpdate()`) to prevent duplicate notifications from concurrent executions
3. Tracks sent notifications via `last_notification_sent_at` column
4. For recurring notifications: Updates `notification_datetime` to next occurrence
5. For one-time notifications: Sets `has_notification=false` after sending

**Ntfy Integration**: Uses `wijourdil/ntfy-notification-channel` package. Configure via:
- `NTFY_SERVER_URL` (default: https://ntfy.sh)
- `NTFY_TOPIC` (default: my-notes-reminders)

### Timezone Handling
The application handles timezone conversion for notifications:
- Frontend sends `timezone_offset` (in minutes, from JavaScript's `getTimezoneOffset()`)
- Backend converts local datetime to UTC by **adding** the offset
- Example: Mountain Time (UTC-7) sends offset=420; local 6:04 PM + 420 minutes = UTC 1:04 AM

This logic is in `NoteController::store()` and `NoteController::update()` at lines 48-56 and 117-126.

### File Attachments
- Stored in `storage/app/public/attachments/`
- Metadata (original name, path, mime type, size) saved as JSON in `attachments` column
- Deletion handled in `NoteController::destroy()` and `NoteController::update()` (for individual removals)

### Views & Frontend
- Blade templates in `resources/views/notes/`
- TailwindCSS 4.0 for styling
- Vite for asset bundling
- Markdown rendered to HTML via `league/commonmark` (see `Note::getRenderedContentAttribute()`)

### Routes
All routes in `routes/web.php`:
- `GET /` - List all notes
- `POST /notes` - Create note
- `GET /notes/{note}` - Show note
- `GET /notes/{note}/edit` - Get note data (JSON)
- `PUT /notes/{note}` - Update note
- `DELETE /notes/{note}` - Delete note

### Queue System
Uses database queue driver (`QUEUE_CONNECTION=database`). Jobs table created via migration. Start worker with `php artisan queue:listen` or `php artisan queue:work`.

## Production Deployment (Laravel Forge)

### Scheduler Setup
The scheduler is configured in `bootstrap/app.php` to run `notes:send-reminders` every minute.

**In Forge Dashboard:**
1. Go to **Commands** → **Scheduled Jobs**
2. Add command: `cd /home/forge/your-domain.com && php artisan notes:send-reminders`
3. Set frequency: **Every Minute**

**Important:** Include `cd /home/forge/your-domain.com &&` before the command, or use the full path `php /home/forge/your-domain.com/artisan`, otherwise you'll get "Could not open input file: artisan" error.

### Queue Worker Setup
**In Forge Dashboard:**
1. Go to **Processes** → **New Daemon**
2. Command: `php artisan queue:work --sleep=3 --tries=3`
3. Forge will manage it with Supervisor automatically

### Local Development Scheduler
For local development, the scheduler needs to run separately from `composer dev`:
```bash
# In a separate terminal:
php artisan schedule:work
```

The `composer dev` script starts server, queue worker, and Vite but not the scheduler.

## Testing Notifications

To test the notification system manually:
```bash
# Set your topic in .env
NTFY_TOPIC=chit

# Test sending a notification
curl -d "Test notification from command line" https://ntfy.sh/chit

# Or via artisan command
NTFY_TOPIC=chit php artisan notes:send-reminders
```
