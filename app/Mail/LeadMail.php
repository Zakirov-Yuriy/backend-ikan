<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/* Письмо «Новая заявка с сайта» */
class LeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead, public bool $test = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->test ? 'Проверка: ' : '').'Новая заявка с сайта ИКАН: '.$this->lead->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.lead', with: ['lead' => $this->lead, 'test' => $this->test]);
    }
}
