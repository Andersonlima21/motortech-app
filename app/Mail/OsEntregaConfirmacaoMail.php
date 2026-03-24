<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OsEntregaConfirmacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $osId,
        public readonly string $confirmUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "MotorTech - Confirmação de entrega da OS #{$this->osId}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.os_entrega_confirmacao',
            with: [
                'osId' => $this->osId,
                'confirmUrl' => $this->confirmUrl,
            ],
        );
    }
}
