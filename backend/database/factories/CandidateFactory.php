<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'current_company' => $this->faker->company,
            'current_position' => $this->faker->jobTitle,
            'source' => $this->faker->randomElement(['website', 'linkedin', 'referral', 'agency']),
            'status' => $this->faker->randomElement(['new', 'screened', 'interviewed', 'offered', 'hired', 'rejected']),
        ];
    }
}
