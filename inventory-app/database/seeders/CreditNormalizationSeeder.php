<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Normalize all customer credit_limit to USD.
 * Old data had Rupiah values — replace with $300 default.
 * New customers default to $300 USD.
 */
class CreditNormalizationSeeder extends Seeder
{
    public function run(): void
    {
        // Set semua customer ke default $300.00 USD
        // (credit_limit > 1000 kemungkinan masih dalam Rupiah)
        DB::table('customers')
            ->where('credit_limit', '>', 999)
            ->update(['credit_limit' => 300.00, 'credit_used' => 0.00]);

        // Customer dengan credit sangat kecil (< 10) juga reset ke $300
        DB::table('customers')
            ->where('credit_limit', '<', 10)
            ->where('credit_limit', '>', 0)
            ->update(['credit_limit' => 300.00]);

        $this->command->info('✅ Credit limits normalized to $300.00 USD for all customers.');
    }
}
