<?php
// app/Mail/LessonReminder.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\LessonSchedule;
use App\Models\User;

class LessonReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LessonSchedule $lesson,
        public User $user,
        public ?string $customMessage = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Herinnering: Tennisles morgen',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lesson-reminder',
        );
    }
}
