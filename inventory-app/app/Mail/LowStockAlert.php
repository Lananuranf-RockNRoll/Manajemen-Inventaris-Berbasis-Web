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

    /**
     * @param Collection $items  — koleksi Inventory model dengan relasi product & warehouse
     * @param bool       $isRealtime  — true = dipicu real-time saat stok turun, false = daily digest
     */
    public function __construct(
        public Collection $items,
        public bool       $isRealtime = true,
    ) {}

    public function envelope(): Envelope
    {
        $now    = now()->timezone('Asia/Jakarta');
        $prefix = $this->isRealtime ? '🚨 [Real-time]' : '📋 [Harian]';
        $date   = $now->format('d/m/Y H:i') . ' WIB';

        return new Envelope(
            subject: "{$prefix} Peringatan Stok Rendah — {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
        );
    }
}
