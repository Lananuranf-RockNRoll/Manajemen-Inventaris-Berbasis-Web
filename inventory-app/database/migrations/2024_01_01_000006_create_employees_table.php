<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('department', 50)->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
