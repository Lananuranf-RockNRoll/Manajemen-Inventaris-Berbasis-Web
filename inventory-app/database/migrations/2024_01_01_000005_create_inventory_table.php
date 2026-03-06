<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_on_hand')->default(0);
            $table->integer('qty_reserved')->default(0);
            $table->integer('min_stock')->default(10);
            $table->integer('max_stock')->default(1000);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'uq_product_warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
