<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $items)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ALERT] Stok Rendah - ' . $this->items->count() . ' Produk',
        );
    }

    public function content(): Content
    {
        // TODO: Buat view resources/views/emails/low-stock-alert.blade.php
        return new Content(
            view: 'emails.low-stock-alert',
        );
    }
}
