<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * L06: KHÔNG được đặt tên là $subject vì Illuminate\Mail\Mailable đã có
     * public $subject (untyped) -> khai báo lại sẽ gây fatal
     * "Type of ...::$subject must not be defined". Dùng tên riêng rồi truyền
     * sang view qua Content::with() để giữ nguyên biến $subject/$message của view.
     */
    public string $mailSubject;
    public string $mailMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $message)
    {
        $this->mailSubject = $subject;
        $this->mailMessage = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.student-notification',
            with: [
                'subject' => $this->mailSubject,
                'message' => $this->mailMessage,
            ],
        );
    }
}
