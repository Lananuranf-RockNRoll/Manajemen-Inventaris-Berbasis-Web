<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Konversi credit_limit dan credit_used dari Rupiah ke USD
        // Default baru: 300.00 USD
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(300.00)->change();
            $table->decimal('credit_used', 12, 2)->default(0.00)->change();
        });

        // Set semua customer yang credit_limit = 0 ke default $300
        DB::table('customers')
            ->where('credit_limit', 0)
            ->update(['credit_limit' => 300.00]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 10, 2)->default(0.00)->change();
            $table->decimal('credit_used', 10, 2)->default(0.00)->change();
        });
    }
};
