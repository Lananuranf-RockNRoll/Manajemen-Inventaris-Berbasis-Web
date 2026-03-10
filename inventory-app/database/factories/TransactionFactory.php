<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('??????')),
            'customer_id'  => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'employee_id'  => null,
            'status'       => 'pending',
            'order_date'   => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'shipped_date' => null,
            'total_amount' => $this->faker->randomFloat(2, 50, 5000),
            'notes'        => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function shipped(): static
    {
        return $this->state([
            'status'       => 'shipped',
            'shipped_date' => now()->toDateString(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state([
            'status'       => 'delivered',
            'shipped_date' => now()->subDays(2)->toDateString(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(['status' => 'canceled']);
    }
}
