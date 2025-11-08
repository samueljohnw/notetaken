<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('markdown_file_path')->unique();
            $table->json('attachments')->nullable();
            $table->boolean('has_notification')->default(false);
            $table->dateTime('notification_datetime')->nullable();
            $table->string('notification_recurrence')->nullable(); // daily, weekly, monthly, yearly
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
