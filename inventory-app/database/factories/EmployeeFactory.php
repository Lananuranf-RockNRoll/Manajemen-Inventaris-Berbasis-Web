<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id'      => null,
            'warehouse_id' => Warehouse::factory(),
            'name'         => $this->faker->name(),
            'email'        => $this->faker->unique()->safeEmail(),
            'phone'        => $this->faker->phoneNumber(),
            'job_title'    => $this->faker->jobTitle(),
            'department'   => $this->faker->randomElement(['Sales', 'Warehouse', 'Finance', 'IT']),
            'hire_date'    => $this->faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'salary'       => $this->faker->randomFloat(2, 2000, 10000),
            'is_active'    => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
