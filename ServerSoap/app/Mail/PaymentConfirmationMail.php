<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public float $amount;
    public string $clientName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, float $amount, string $clientName)
    {
        $this->token = $token;
        $this->amount = $amount;
        $this->clientName = $clientName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de Pago - Token de Seguridad',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_confirmation',
        );
    }
}
