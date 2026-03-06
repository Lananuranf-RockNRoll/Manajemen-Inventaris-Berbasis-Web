<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLowStockAlerts extends Command
{
    protected $signature   = 'inventory:low-stock-alerts';
    protected $description = 'Send low stock alert emails to admins and managers';

    public function handle(): int
    {
        $lowStockItems = Inventory::with(['product.category', 'warehouse'])
            ->lowStock()
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('Tidak ada produk dengan stok rendah.');
            return self::SUCCESS;
        }

        $recipients = User::whereIn('role', ['admin', 'manager'])
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('Tidak ada penerima (admin/manager aktif).');
            return self::SUCCESS;
        }

        foreach ($recipients as $user) {
            Mail::to($user->email)->queue(new LowStockAlert($lowStockItems));
        }

        $this->info("Alert terkirim ke {$recipients->count()} penerima untuk {$lowStockItems->count()} produk.");

        return self::SUCCESS;
    }
}
