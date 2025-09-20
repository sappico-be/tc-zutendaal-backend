<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\LessonRegistration;
use App\Models\LessonPackage;

class PaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LessonRegistration $registration,
        public LessonPackage $package
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Herinnering: Betaling Tennislessen - ' . $this->package->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
        );
    }

    public function build()
    {
        $amount = $this->registration->user->membership_type === 'non_member' 
            ? $this->package->price_non_members 
            : $this->package->price_members;

        return $this->view('emails.payment-reminder')
            ->with([
                'userName' => $this->registration->user->name,
                'packageName' => $this->package->name,
                'amount' => $amount,
                'dueDate' => $this->package->registration_deadline->addDays(7)->format('d-m-Y'),
                'paymentInfo' => [
                    'account' => 'BE12 3456 7890 1234',
                    'name' => 'TC Zutendaal VZW',
                    'reference' => 'TENNIS-' . $this->package->id . '-' . $this->registration->id,
                ]
            ]);
    }
}
