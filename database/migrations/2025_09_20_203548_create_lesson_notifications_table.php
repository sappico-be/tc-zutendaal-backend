<?php
// database/migrations/2025_01_XX_create_lesson_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_schedule_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['reminder', 'cancelled', 'changed', 'new'])->default('reminder');
            $table->enum('channel', ['email', 'sms', 'both'])->default('email');
            $table->text('message');
            $table->integer('recipients_count')->default(0);
            $table->json('recipients')->nullable(); // Store user IDs who received it
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['lesson_schedule_id', 'type']);
            $table->index('status');
        });

        // Settings voor automatische herinneringen
        Schema::create('lesson_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_package_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->integer('days_before')->default(1); // Dagen voor de les
            $table->time('send_time')->default('19:00'); // Tijd om te versturen
            $table->enum('channel', ['email', 'sms', 'both'])->default('email');
            $table->text('email_template')->nullable();
            $table->text('sms_template')->nullable();
            $table->timestamps();
            
            $table->unique('lesson_package_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_notifications');
        Schema::dropIfExists('lesson_reminder_settings');
    }
};
