<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\LessonSchedule;
use App\Models\User;

class LessonChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LessonSchedule $lesson,
        public User $user,
        public array $changes = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'WIJZIGING: Tennisles ' . $this->lesson->lesson_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lesson-changed',
        );
    }
}
