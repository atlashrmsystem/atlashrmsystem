<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle,
            'department_id' => Department::inRandomOrder()->first()->id ?? Department::factory(),
            'location' => $this->faker->city,
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(['draft', 'published', 'closed']),
            'posted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'closes_at' => $this->faker->dateTimeBetween('now', '+2 months'),
            'created_by' => User::inRandomOrder()->first()->id ?? User::factory(),
        ];
    }
}
