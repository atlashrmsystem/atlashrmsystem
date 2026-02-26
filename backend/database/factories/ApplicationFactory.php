<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::inRandomOrder()->first()->id ?? JobPosting::factory(),
            'candidate_id' => Candidate::inRandomOrder()->first()->id ?? Candidate::factory(),
            'cover_letter' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['under_review', 'moved_to_interview', 'rejected']),
            'applied_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
